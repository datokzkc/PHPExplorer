import sys
import websocket
from enum import Enum
import logging
import time
import ujson as json
import glob
import datetime
import os
import threading
import auApi
import candleChart
import stock_list
import Yobine
import queue

class msgGetThread(threading.Thread):
        ## 受信したメッセージをファイル保存するか
    save_recv_msg_fg = False   ##*********ダンプデータテスト時はFalseに設定すること*********
    recv_msg_log_lv = logging.DEBUG-1

    @classmethod
    def set_class_logger(cls, logger):
        cls.logger = logger
        cls.save_recv_msg_fg = True
    
    def __init__(self, msg):
        super().__init__()
        self._msg = msg

    def run(self):
        # 処理
        if self.save_recv_msg_fg:
            self.logger.log(self.recv_msg_log_lv,self.msg)
        res_data = json.loads(self.msg)
        #適切なスレッドにメッセージを送信

class TestTiming(Enum):
    TIMING_FAST = 0  #即時約定
    TIMING_JUST = 1  #現在値が注文値と同じになるか、最良買気配値段が注文値以下で約定したものとする
    TIMING_OVER = 2  #現在値が注文値より安くなるか、最良買気配値段が注文値以下で約定したものとする

class StockControlThread(threading.Thread):

    class OrderStatus(Enum):
        #状態(["Status"])一覧
        NO_ORDER_HOLD=0
        ORDER_BUY=11
        ORDER_SELL=12
        ORDER_BUY_CLOSE = 21
        ORDER_SELL_CLOSE = 22
        HOLD_BUY=31
        HOLD_SELL=32
        ORDER_BUSY=99 ##注文発注中など、処理途中を示す

    class OrderType(Enum):
        #注文リクエスト伝達用
        NO_NEW_ORDER = 0
        NEW_ORDER_BUY = 1
        NEW_ORDER_SELL = 2
        NEW_ORDER_BUY_CLOSE = 3
        NEW_ORDER_SELL_CLOSE = 4
        CANCEL_ORDER_BUY = 11
        CANCEL_ORDER_SELL = 12
        CANCEL_ORDER_BUY_CLOSE = 13
        CANCEL_ORDER_SELL_CLOSE = 14

    candle_term = datetime.timedelta(minutes=1) #1 minitus
    candle_max = 5 # [0]-[CANDLE_MAX-1]

    order_info={}

    total_result = 0.0  ##合計の勝ち負けの数
    remain_money = 0

    exec_real_order_fg = True   ##注文を実際に行うか
    order_lots = 1 ##最小単元で発注。(複数単元の場合、一部約定などありめんどい)
    test_req_sec = 0.25 ##テスト用途で待つ時間(実際にアクセスする際にかかる時間)※少数もOK

    execution_timing = TestTiming.TIMING_OVER 

    ## 負荷軽減間引き用パラメータ
    order_check_interbal = 1 ##注文状況確認の間引き感覚
    force_close_int_cnt = 0

    ## 過去データを利用したテストモード用フラグ
    test_mode_fg = False

    logger = logging.getLogger(__name__)

    def __init__(self,kabu_api:auApi.kabusApi,symbol_str:str):
        super(StockControlThread, self).__init__()
        self.data_que = queue.Queue(5)
        self.candle_data = candleChart.candleDatas()
        self.order_work_data = {}
        self.order_work_data["Status"]=self.OrderStatus.NO_ORDER_HOLD
        self.kabu_api = kabu_api
        self.symbol_str = symbol_str
        self.name = self.symbol_str+"ControlThread"

    def thread_func(self):
        while True: #TODO 無限ループの脱出条件を入れる必要あり
            try:
                res_data = self.data_que.get(timeout=2)  # 2秒待機してメッセージを取得
                self.logger.debug('--- RECV MSG. --- ')

                # res_dataのチェック処理

                #symbolは特定のもののみが送られるものとして仮定 #TODO 必要に応じてチェック処理を入れる
                
                if self.test_mode_fg == False:
                    ## 最新価格が今日でなければスキップ
                    now_datetime = datetime.datetime.now()
                    today_str = now_datetime.strftime('%Y-%m-%d')
                    if res_data.get("CurrentPriceTime") != None:
                        if res_data["CurrentPriceTime"].startswith(today_str) == False:
                            self.logger.debug("Receve message ,But CurrentPrice is not today data, So Skip")
                            continue
                    else:
                        self.logger.debug("Receve message ,But CurrentPrice Time data is None, So Skip")
                        continue
                else: ## テストではCurrentPriceTimeは必ず入っているので、それをtodayとして利用
                    now_datetime = datetime.datetime.strptime(res_data["CurrentPriceTime"],'%Y-%m-%dT%H:%M:%S+09:00')
                current_price=res_data["CurrentPrice"]
                if isinstance(current_price, (int, float)) == False:
                    self.logger.warning("Receve message ,But CurrentPrice is not numberic, So Skip")
                    continue

            except queue.Empty:
                res_data = None

            self.update_order_status(res_data)

            if res_data == None:
                continue

            ##***** ローソク足更新 ******
            current_price = res_data["CurrentPrice"]
            ret = self.candle_data.add_data(current_price,now_datetime)
            if ret == candleChart.RET_NEW_CANDLE:
                self.logger.debug('New Candle Create ')
            elif ret == candleChart.RET_UPDATE_CANDLE_HIGH:
                self.logger.debug('New Candle High Price ')
            elif ret == candleChart.RET_UPDATE_CANDLE_LOW :
                self.logger.debug('New Candle Low Price ')

            judge_now, order_price = self.judge_trend(res_data)


            ## ****** 判断結果をもとに注文発注 **********

            stock_info_log_fg = self.order_exec(now_datetime,judge_now, order_price)

            #TODO total_resultとremain_moneyは別クラス(Walletクラスとか？)で作って共通アクセスさせるか
            total_result_str = '{:f}'.format(total_result)
            if (stock_info_log_fg == True):
                self.logger.info("GetTime:"+ res_data["CurrentPriceTime"] +" Symbol:"+self.symbol_str+" Price:"+str(current_price))
                self.logger.info(" Total_Result:"+total_result_str)
                self.logger.info("remain_money=%d",remain_money)
            else:
                self.logger.debug("GetTime:"+ res_data["CurrentPriceTime"] +" Symbol:"+self.symbol_str+" Price:"+str(current_price))
                self.logger.debug(" Total_Result:"+total_result_str)
                self.logger.debug("remain_money=%d",remain_money)
        
        logger.info("Thread Ended.")

    #TODO ここから下実装すること

    def update_order_status(self,res_data:dict):
    ## ******* 注文状況更新  *********
        if order_work_data["Status"] == ORDER_BUY:
            if EXEC_REAL_ORDER_FG:
                if order_work_data["ChkInt"] > 0:
                    logger.debug("Order Check Skip..For Interbal")
                    order_work_data["ChkInt"] = order_work_data["ChkInt"] - 1
                else:
                    order_res = kabu_api.GetOrderStatus(order_work_data["OrderId"])
                    if order_res == None:
                        logger.error("GetOrderStstus Error!!")
                        raise RuntimeError("GetOrderStatus Error!!")
                    order_res = order_res[0] #帰ってくるのは配列なので、ヒットした１か所目のみ抽出
                    if order_res.get("State")== 5:
                        if order_res.get("CumQty") != None and order_res.get("CumQty") > 0:
                            #全約定したとき
                            sum_price = 0.0
                            sum_qty = 0.0
                            for detail_data in order_res["Details"]:
                                if detail_data["RecType"] == 8:  ##約定状況レコードのみ処理
                                    sum_price = sum_price + detail_data["Price"] * detail_data["Qty"]
                                    sum_qty = sum_qty + detail_data["Qty"]
                            if sum_qty == 0:
                                logger.error("Order Details does NOT have result Data . Qty = 0")
                                #状況更新などはしない
                            else:
                                open_price = sum_price / sum_qty ##平均約定価格を入手価格とする
                                order_work_data["Status"] = HOLD_BUY
                                order_work_data["OpenPrice"] = open_price
                                logger.info('Success Buy Stock Symbol=%s Price=%f',symbolStr,open_price)
                                remain_money = remain_money - sum_price
                                logger.info(" remain_money=%d",remain_money)
                        else:
                            #発注エラーの時
                            logger.error("Order ID %s is Error.",order_work_data["OrderId"])
                            #とりあえず、注文がなかったものとして引き続き処理
                            order_work_data["Status"] = NO_ORDER_HOLD
                    order_work_data[symbolStr]["ChkInt"] = OLDER_CHK_INTERBAL
            else:  #テスト用処理
            
                close_now_fg = False
                if execution_timing == TIMING_FAST:
                    #即時約定
                    close_now_fg = True
                if execution_timing == TIMING_JUST:
                    # 現在値が注文値と同じになるか、最良買気配値段が注文値以下で約定したものとする
                    if order_work_data[symbolStr]["OpenPrice"] >= current_price or order_work_data[symbolStr]["OpenPrice"] >= resdata["AskPrice"]:
                        close_now_fg = True
                if execution_timing == TIMING_OVER:
                    # 現在値が注文値より安くなるか、最良買気配値段が注文値以下で約定したものとする
                    if order_work_data[symbolStr]["OpenPrice"] > current_price or order_work_data[symbolStr]["OpenPrice"] >= resdata["AskPrice"]:
                        close_now_fg = True

                if close_now_fg:
                    time.sleep(TEST_RES_SEC)
                    order_work_data["Status"] = HOLD_BUY
                    #テストではorder_work_data[symbolStr]["OpenPrice"]更新しない。最悪値で約定したものとする
                    logger.info('Success Buy Stock Symbol=%s Price=%f',symbolStr,order_work_data["OpenPrice"])
                    remain_money = remain_money - (order_work_data["OpenPrice"] * order_info["Order"])
                    logger.info(" remain_money=%d",remain_money)
                #order_work_data[symbolStr]["ChkInt"] = OLDER_CHK_INTERBAL  #テストでは負荷軽減用インターバルはなし。

        elif order_work_data["Status"] == ORDER_SELL:
            if EXEC_REAL_ORDER_FG:
                if order_work_data["ChkInt"] > 0:
                    logger.debug("Order Check Skip..For Interbal")
                    order_work_data[symbolStr]["ChkInt"] = order_work_data["ChkInt"] - 1
                else:
                    order_res = kabu_api.GetOrderStatus(order_work_data["OrderId"])
                    if order_res == None:
                        logger.error("GetOrderStstus Error!!")
                        raise RuntimeError("GetOrderStatus Error!!")
                    order_res = order_res[0] #帰ってくるのは配列なので、ヒットした１か所目のみ抽出
                    if order_res.get("State")== 5:
                        if order_res.get("CumQty") != None and order_res.get("CumQty") > 0:
                            #全約定したとき
                            sum_price = 0.0
                            sum_qty = 0.0
                            for detail_data in order_res["Details"]:
                                if detail_data["RecType"] == 8:  ##約定状況レコードのみ処理
                                    sum_price = sum_price + detail_data["Price"] * detail_data["Qty"]
                                    sum_qty = sum_qty + detail_data["Qty"]
                            if sum_qty == 0:
                                logger.error("Order Details does NOT have result Data . Qty = 0")
                                #状況更新などはしない
                            else:
                                open_price = sum_price / sum_qty ##平均約定価格を入手価格とする
                                order_work_data["Status"] = HOLD_SELL
                                order_work_data["OpenPrice"] = open_price
                                logger.info('Success Buy Stock Symbol=%s Price=%f',symbolStr,open_price)
                                remain_money = remain_money - sum_price
                                logger.info(" remain_money=%d",remain_money)
                        else:
                            #発注エラーの時
                            logger.error("Order ID %s is Error.",order_work_data["OrderId"])
                            #とりあえず、注文がなかったものとして引き続き処理
                            order_work_data["Status"] = NO_ORDER_HOLD
                order_work_data["ChkInt"] = OLDER_CHK_INTERBAL
            else:   #テスト用処理

                close_now_fg = False
                if execution_timing == TIMING_FAST:
                    #即時約定
                    close_now_fg = True
                if execution_timing == TIMING_JUST:
                    # 現在値が注文値と同じになるか、最良買気配値段が注文値以下で約定したものとする
                    if order_work_data["OpenPrice"] <= current_price or order_work_data["OpenPrice"] <= res_data["BidPrice"]: 
                        close_now_fg = True
                if execution_timing == TIMING_OVER:
                    # 値が注文値より高くなるか、最良買気配値段が注文値以上で約定したものとする
                    if order_work_data["OpenPrice"] < current_price or order_work_data["OpenPrice"] <= res_data["BidPrice"]: 
                        close_now_fg = True  

                if close_now_fg:
                    time.sleep(TEST_RES_SEC)
                    order_work_data["Status"] = HOLD_SELL
                    #テストではorder_work_data["OpenPrice"]更新しない。最悪値で約定したものとする
                    logger.info('Success Buy Stock Symbol=%s Price=%f',symbolStr,order_work_data["OpenPrice"])
                    remain_money = remain_money - (order_work_data["OpenPrice"] * order_info["Order"])
                    logger.info(" remain_money=%d",remain_money)
                #order_work_data["ChkInt"] = OLDER_CHK_INTERBAL  #テストでは負荷軽減用インターバルはしない


        elif order_work_data["Status"] == ORDER_BUY_CLOSE:
            if EXEC_REAL_ORDER_FG:
                if order_work_data["ChkInt"] > 0:
                    logger.debug("Order Check Skip..For Interbal")
                    order_work_data["ChkInt"] = order_work_data["ChkInt"] - 1
                else:
                    order_res = kabu_api.GetOrderStatus(order_work_data["OrderId"])
                    if order_res == None:
                        logger.error("GetOrderStstus Error!!")
                        raise RuntimeError("GetOrderStatus Error!!")
                    
                    order_res = order_res[0] #帰ってくるのは配列なので、ヒットした１か所目のみ抽出
                    if order_res.get("State")== 5:
                        if order_res.get("CumQty") != None and order_res.get("CumQty") > 0:
                            #全約定したとき
                            sum_price = 0.0
                            sum_qty = 0.0
                            for detail_data in order_res["Details"]:
                                if detail_data["RecType"] == 8:  ##約定状況レコードのみ処理
                                    sum_price = sum_price + detail_data["Price"] * detail_data["Qty"]
                                    sum_qty = sum_qty + detail_data["Qty"]
                            if sum_qty == 0:
                                logger.error("Order Details does NOT have result Data . Qty = 0")
                                #状況更新などはしない
                            else: 
                                close_price = sum_price / sum_qty ##平均約定価格をclose価格とする
                                order_work_data["Status"] = NO_ORDER_HOLD
                                order_work_data["ClosePrice"] = close_price
                                logger.info('Success Close Buy Position  Symbol=%s Price=%f',symbolStr,close_price)
                                total_result = total_result + ((close_price - order_work_data["OpenPrice"])* sum_qty)
                                logger.info(" total_result=%d",total_result)
                        else:
                            #発注エラーの時
                            logger.error("Order ID %s is Error.",order_work_data["OrderId"])
                            #とりあえず、注文がなかったものとして引き続き処理
                            order_work_data["Status"] = HOLD_BUY
                    order_work_data["ChkInt"] = OLDER_CHK_INTERBAL

            else:   #テスト用処理

                close_now_fg = False
                if execution_timing == TIMING_FAST:
                    #即時約定
                    close_now_fg = True
                if execution_timing == TIMING_JUST:
                    # 現在値が注文値と同じになるか、最良買気配値段が注文値以下で約定したものとする
                    if order_work_data["ClosePrice"] <= current_price or order_work_data["ClosePrice"] <= res_data["BidPrice"]:
                        close_now_fg = True
                if execution_timing == TIMING_OVER:
                    # 現在値が注文値より高くなるか、最良買気配値段が注文値以上で約定したものとする
                    if order_work_data["ClosePrice"] < current_price or order_work_data["ClosePrice"] <= res_data["BidPrice"]:
                        close_now_fg = True  

                if close_now_fg:
                    # テスト用途処理
                    time.sleep(TEST_RES_SEC)
                    order_work_data[symbolStr]["Status"] = NO_ORDER_HOLD
                    #テストではorder_work_data[symbolStr]["ClosePrice"]更新しない。最悪値で約定したものとする
                    logger.info('Success Close Buy Position  Symbol=%s Price=%f',symbolStr,order_work_data["ClosePrice"])
                    total_result = total_result + ((order_work_data["ClosePrice"] - order_work_data["OpenPrice"])*order_info["Order"]*ORDER_LOTS)
                    logger.info(" total_result=%d",total_result)
                #order_work_data["ChkInt"] = OLDER_CHK_INTERBAL #テスト用では負荷軽減用インターバルはしない

                
        elif order_work_data["Status"] == ORDER_SELL_CLOSE:
            if EXEC_REAL_ORDER_FG:
                if order_work_data["ChkInt"] > 0:
                    logger.debug("Order Check Skip..For Interbal")
                    order_work_data["ChkInt"] = order_work_data["ChkInt"] - 1
                else:

                    order_res = kabu_api.GetOrderStatus(order_work_data["OrderId"])
                    if order_res == None:
                        logger.error("GetOrderStstus Error!!")
                        raise RuntimeError("GetOrderStatus Error!!")
                    
                    order_res = order_res[0] #帰ってくるのは配列なので、ヒットした１か所目のみ抽出
                    if order_res.get("State")== 5:
                        if order_res.get("CumQty") != None and order_res.get("CumQty") > 0:
                            #全約定したとき
                            sum_price = 0.0
                            sum_qty = 0.0
                            for detail_data in order_res["Details"]:
                                if detail_data["RecType"] == 8:  ##約定状況レコードのみ処理
                                    sum_price = sum_price + detail_data["Price"] * detail_data["Qty"]
                                    sum_qty = sum_qty + detail_data["Qty"]
                            if sum_qty == 0:
                                logger.error("Order Details does NOT have result Data . Qty = 0")
                                #状況更新などはしない
                            else: 
                                close_price = sum_price / sum_qty ##平均約定価格をclose価格とする
                                order_work_data["Status"] = NO_ORDER_HOLD
                                order_work_data["ClosePrice"] = close_price
                                logger.info('Success Close Sell Position  Symbol=%s Price=%f',symbolStr,close_price)
                                total_result = total_result + ((order_work_data["OpenPrice"] - close_price)* sum_qty)
                                logger.info(" total_result=%d",total_result)
                        else:
                            #発注エラーの時
                            logger.error("Order ID %s is Error.",order_work_data["OrderId"])
                            #とりあえず、注文がなかったものとして引き続き処理
                            order_work_data["Status"] = HOLD_SELL
                    order_work_data["ChkInt"] = OLDER_CHK_INTERBAL
            else:  #テスト用処理
                close_now_fg = False
                if execution_timing == TIMING_FAST:
                    #即時約定
                    close_now_fg = True
                if execution_timing == TIMING_JUST:
                    # 現在値が注文値と同じになるか、最良買気配値段が注文値以下で約定したものとする
                    if order_work_data["ClosePrice"] >= currentPrice or order_work_data["ClosePrice"] >= res_data["AskPrice"]:
                        close_now_fg = True
                if execution_timing == TIMING_OVER:
                    # 現在値が注文値より安くなるか、最良買気配値段が注文値以下で約定したものとする
                    if order_work_data["ClosePrice"] > currentPrice or order_work_data["ClosePrice"] >= res_data["AskPrice"]:
                        close_now_fg = True  

                if close_now_fg:
                    # テスト用途処理
                    time.sleep(TEST_RES_SEC)
                    order_work_data["Status"] = NO_ORDER_HOLD
                    #テストではorder_work_data[symbolStr]["ClosePrice"]更新しない。最悪値で約定したものとする
                    logger.info('Success Close Sell Position  Symbol=%s Price=%f',symbolStr,order_work_data["ClosePrice"])
                    total_result = total_result + ((order_work_data["OpenPrice"] - order_work_data["ClosePrice"])*order_info["Order"]*ORDER_LOTS)
                    logger.info(" total_result=%d",total_result)
                order_work_data["ChkInt"] = OLDER_CHK_INTERBAL


    def judge_trend(self,res_data:dict):

        ## ******** 注文判断  *********
        judge_now = NO_NEW_ORDER
        order_price = None

        # 呼び値を使用するため、呼び値単位が計算されていない場合は、計算
        if order_info.get("YobineScale") == None:
            order_info["YobineScale"] = Yobine.GetYobineScale(order_info["YobineGroup"],current_price)

        if candle_data.is_full() == False:
            logger.debug("candle number is low. so skip to jugge")
        else:
            if order_work_data["Status"] == NO_ORDER_HOLD:
                if is_buy_entry(candle_data,symbolStr,res_data,current_price):
                    #買いサインが出ている場合
                    judge_now = NEW_ORDER_BUY
                    order_price = currentPrice
                elif is_sell_entry(candle_data,symbolStr,res_data,current_price):
                    #売りサインが出ている場合
                    judge_now = NEW_ORDER_SELL
                    order_price = currentPrice
            elif order_work_data["Status"] == HOLD_BUY:
                if is_buy_close(candle_data,symbolStr,res_data,current_price):
                    #クローズサインが出ている場合
                    judge_now = NEW_ORDER_BUY_CLOSE
                    if current_price <= order_work_data["OpenPrice"] + (-5 * order_info["YobineScale"]):
                        #逆に動いている場合は損切の値段で注文
                        order_price = current_price
                    else:
                        #通常は利益確定の値段(5ティック上まで上がったら利食い)
                        order_price = order_work_data["OpenPrice"] + (5 * order_info["YobineScale"])
            elif order_work_data["Status"] == HOLD_SELL:
                if is_sell_close(candle_data,symbolStr,res_data,current_price):
                    #クローズサインが出ている場合
                    judge_now = NEW_ORDER_SELL_CLOSE
                    if current_price >= order_work_data["OpenPrice"] + (5 * order_info["YobineScale"]):
                        #逆に動いている場合は損切の値段で注文
                        order_price = current_price
                    else:
                        #通常は利益確定の値段(5ティック下まで下がったら利食い)
                        order_price = order_work_data[symbolStr] + (-5 * order_info["YobineScale"])
            elif order_work_data["Status"] == ORDER_BUY:
                if is_cancel_buy(candle_data,symbolStr,res_data,current_price):
                    #新規買い注文を取り消し
                    judge_now = CANCEL_ORDER_BUY
            elif order_work_data["Status"] == ORDER_SELL:
                if is_cancel_sell(candle_data,symbolStr,res_data,current_price):
                    #新規売り注文を取り消し
                    judge_now = CANCEL_ORDER_SELL
            elif order_work_data["Status"] == ORDER_BUY_CLOSE:
                if CLOSE_ORDER_CANCEL_FG and is_cancel_buy_close(candle_data,symbolStr,res_data,current_price):
                    #決済注文を取り消し
                    judge_now = CANCEL_ORDER_BUY_CLOSE
            elif order_work_data[symbolStr]["Status"] == ORDER_SELL_CLOSE:
                if CLOSE_ORDER_CANCEL_FG and is_cancel_sell_close(candle_data,symbolStr,res_data,current_price):
                    #決済注文を取り消し
                    judge_now = CANCEL_ORDER_SELL_CLOSE

        return judge_now, order_price


    def is_buy_entry(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        retval = True

        for i in range(candle_data.candle_max - 2, 1, -1):
            if (candle_data.candle_close[i] - candle_data.candle_open[i]) < (candle_data.candle_close[i+1] - candle_data.candle_open[i+1]):
                retval = False
                break

        if retval == True:
            if (candle_data.candle_close[1] - candle_data.candle_open[1]) <= 0:
                retval = False

        return retval

    def is_sell_entry(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        retval = True

        for i in range(candle_data.candle_max - 2, 1, -1):
            if (candle_data.candle_close[i] - candle_data.candle_open[i]) > (candle_data.candle_close[i+1] - candle_data.candle_open[i+1]):
                retval = False
                break

        if retval == True:
            if (candle_data.candle_close[1] - candle_data.candle_open[1]) >= 0:
                retval = False
            
        return retval

    def is_buy_close(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        #反対注文はすぐに出すものとする
        return True

    def is_sell_close(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        #反対注文はすぐに出すものとする
        return True

    def is_cancel_buy(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        #エントリーサインが消えていた場合注文キャンセル
        return not is_buy_entry(candle_data,symbolStr,resdata,currentPrice)

    def is_cancel_sell(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        #エントリーサインが消えていた場合注文キャンセル
        return not is_sell_entry(candle_data,symbolStr,resdata,currentPrice)

    def is_cancel_buy_close(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        global order_info
        global order_work_data

        #エントリーサインが消えていた場合注文キャンセル
        retval = False
        if order_work_data["OpenPrice"] < order_work_data["ClosePrice"]: #利益確定の注文だった場合
            if currentPrice < order_work_data["OpenPrice"] + (-5 * order_info["YobineScale"]):
                #5ティック以上逆に動いたら損切注文に切り替えるため現在の注文キャンセル
                retval = True
        return retval

    def is_cancel_sell_close(candle_data:candleChart.candleDatas,symbolStr:str,resdata:dict,currentPrice:float):
        global order_info
        global order_work_data
    
        #エントリーサインが消えていた場合注文キャンセル
        retval = False
        if order_work_data["OpenPrice"] > order_work_data["ClosePrice"]: #利益確定の注文だった場合
            if currentPrice > order_work_data["OpenPrice"] + (5 * order_info["YobineScale"]):
                #5ティック以上逆に動いたら損切注文に切り替えるため現在の注文キャンセル
                retval = True
        return retval




    def order_exec(self,now_datetime:datetime.datetime, judge_now:int, order_price:float):
       ## 時間によっては新規決済しない

        stock_info_log_fg = False  ##状況をinfo出力する場合

        CLOSE_TIME = now_datetime.replace(hour=14,minute=40,second=0,microsecond=0)
        FORCE_CLOSE_TIME = now_datetime.replace(hour=14,minute=55,second=0,microsecond=0) ##この時間まで来ると、プログラム管轄外も含め、全銘柄決済注文出す
        if now_datetime < CLOSE_TIME:
            if judge_now == NEW_ORDER_BUY:
                if order_work_data[symbolStr]["Status"] == NO_ORDER_HOLD:
                    #New Order
                    logger.info("<<New BUY Position Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 現在の値段で指値注文
                        org_status = order_work_data[symbolStr]["Status"]
                        #新規発注は重複したら困るので、処理中であることを明記
                        order_work_data[symbolStr]["Status"] = ORDER_BUSY
                        order_res = kabu_api.OrderNew1dayMargin(symbolStr,order_info[symbolStr]["Order"]*ORDER_LOTS,order_price,False,auApi.OrderType.SASHINE)
                        if order_res == None:
                            logger.error("OrderNew1dayMargin Error!!")
                            raise RuntimeError("OrderNew1dayMargin Error!!")
                        if order_res.get("Result") != 0:
                            # 注文発注が失敗した際は、元の状態に戻して、これ以降の処理はしない。
                            order_work_data[symbolStr]["Status"] = org_status
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            order_id = order_res.get("OrderId")
                            order_work_data[symbolStr]["OrderId"] = order_id
                            logger.info("Order id = %s",order_id)
                    else:
                        order_work_data[symbolStr]["Status"] = ORDER_BUSY
                        time.sleep(TEST_RES_SEC)
                        order_work_data[symbolStr]["OrderId"] = "NotRealOrder"

                    order_work_data[symbolStr]["Status"] = ORDER_BUY
                    order_work_data[symbolStr]["OpenPrice"] = order_price
                    stock_info_log_fg = True
                    order_work_data[symbolStr]["ChkInt"] = OLDER_CHK_INTERBAL

            elif judge_now == NEW_ORDER_SELL:
                if order_work_data[symbolStr]["Status"] == NO_ORDER_HOLD:
                    #New Order
                    logger.info("<<New SELL Position Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 現在の値段で指値注文
                        org_status = order_work_data[symbolStr]["Status"]
                        #新規発注は重複したら困るので、処理中であることを明記
                        order_work_data[symbolStr]["Status"] = ORDER_BUSY
                        order_res = kabu_api.OrderNew1dayMargin(symbolStr,order_info[symbolStr]["Order"]*ORDER_LOTS,order_price,True,auApi.OrderType.SASHINE)
                        if order_res == None:
                            logger.error("OrderNew1dayMargin Error!!")
                            raise RuntimeError("OrderNew1dayMargin Error!!")
                        if order_res.get("Result") != 0:
                            # 注文発注が失敗した際は、元の状態に戻して、これ以降の処理はしない。
                            order_work_data[symbolStr]["Status"] = org_status
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            order_id = order_res.get("OrderId")
                            order_work_data[symbolStr]["OrderId"] = order_id
                            logger.info("Order id = %s",order_id)
                    else:
                        order_work_data[symbolStr]["Status"] = ORDER_BUSY
                        time.sleep(TEST_RES_SEC)
                        order_work_data[symbolStr]["OrderId"] = "NotRealOrder"

                    stock_info_log_fg = True
                    order_work_data[symbolStr]["Status"] = ORDER_SELL
                    order_work_data[symbolStr]["OpenPrice"] = order_price
                    order_work_data[symbolStr]["ChkInt"] = OLDER_CHK_INTERBAL

            elif judge_now == NEW_ORDER_BUY_CLOSE:
                if order_work_data[symbolStr]["Status"] == HOLD_BUY:
                    #Close Order
                    logger.info("<<Close BUY Position Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ##  不成で発注
                        order_res = kabu_api.OrderClose1dayMargin(symbolStr,order_info[symbolStr]["Order"]*ORDER_LOTS,order_price,False,auApi.OrderType.FUNARI_GOBA)
                        if order_res == None:
                            logger.error("OrderClose1dayMargin Error!!")
                            raise RuntimeError("OrderClose1dayMargin Error!!")
                        if order_res.get("Result") != 0:
                            #注文失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            order_id = order_res.get("OrderId")
                            order_work_data[symbolStr]["OrderId"] = order_id
                            logger.info("Order id = %s",order_id)
                    else:
                        time.sleep(TEST_RES_SEC)
                        order_work_data[symbolStr]["OrderId"] = "NotRealOrder"

                    stock_info_log_fg = True
                    order_work_data[symbolStr]["Status"] = ORDER_BUY_CLOSE
                    order_work_data[symbolStr]["ClosePrice"] = order_price
                    order_work_data[symbolStr]["ChkInt"] = OLDER_CHK_INTERBAL

            elif judge_now == NEW_ORDER_SELL_CLOSE:
                if order_work_data[symbolStr]["Status"] == HOLD_SELL:
                    #Close Order
                    logger.info("<<Close SELL Position Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 成り行きで発注  ###************** 不成に変更 """"""
                        order_res = kabu_api.OrderClose1dayMargin(symbolStr,order_info[symbolStr]["Order"]*ORDER_LOTS,order_price,True,auApi.OrderType.FUNARI_GOBA)
                        if order_res == None:
                            logger.error("OrderClose1dayMargin Error!!")
                            raise RuntimeError("OrderClose1dayMargin Error!!")
                        if order_res.get("Result") != 0:
                            #注文失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            order_id = order_res.get("OrderId")
                            order_work_data[symbolStr]["OrderId"] = order_id
                            logger.info("Order id = %s",order_id)
                    else:
                        time.sleep(TEST_RES_SEC)
                        order_work_data[symbolStr]["OrderId"] = "NotRealOrder"

                    order_work_data[symbolStr]["Status"] = ORDER_SELL_CLOSE
                    order_work_data[symbolStr]["ClosePrice"] = order_price
                    stock_info_log_fg = True
                    order_work_data[symbolStr]["ChkInt"] = OLDER_CHK_INTERBAL

            elif judge_now == CANCEL_ORDER_BUY:
                if order_work_data[symbolStr]["Status"] == ORDER_BUY:
                    logger.info("<<Cancel BUY Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 注文取消し
                        order_res = kabu_api.CancelOrder(order_work_data[symbolStr]["OrderId"])
                        if order_res == None:
                            logger.error("CancelOrder Error!!")
                            raise RuntimeError("CancelOrder Error!!")
                        if order_res.get("Result") != 0:
                            #注文取り消し失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            logger.info("Success Cancel Order id = %s",order_work_data[symbolStr]["OrderId"])
                    else:
                        time.sleep(TEST_RES_SEC)
                
                    stock_info_log_fg = True
                    ######## NEED FOR PORTING #########
                    # 実は一部約定していた場合もあるため、対処必要
                    order_work_data[symbolStr]["Status"] = NO_ORDER_HOLD

            elif judge_now == CANCEL_ORDER_SELL:
                if order_work_data[symbolStr]["Status"] == ORDER_SELL:
                    logger.info("<<Cancel SELL Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 注文取消し
                        order_res = kabu_api.CancelOrder(order_work_data[symbolStr]["OrderId"])
                        if order_res == None:
                            logger.error("CancelOrder Error!!")
                            raise RuntimeError("CancelOrder Error!!")
                        if order_res.get("Result") != 0:
                            #注文取り消し失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            logger.info("Success Cancel Order id = %s",order_work_data[symbolStr]["OrderId"])
                    else:
                        time.sleep(TEST_RES_SEC)

                    stock_info_log_fg = True
                    ######## NEED FOR PORTING #########
                    # 実は一部約定していた場合もあるため、対処必要
                    order_work_data[symbolStr]["Status"] = NO_ORDER_HOLD

            elif judge_now == CANCEL_ORDER_BUY_CLOSE:
                if order_work_data[symbolStr]["Status"] == ORDER_BUY_CLOSE:
                    logger.info("<<Cancel BUY Close Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 注文取消し
                        order_res = kabu_api.CancelOrder(order_work_data[symbolStr]["OrderId"])
                        if order_res == None:
                            logger.error("CancelOrder Error!!")
                            raise RuntimeError("CancelOrder Error!!")
                        if order_res.get("Result") != 0:
                            #注文取り消し失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            logger.info("Success Cancel Order id = %s",order_work_data[symbolStr]["OrderId"])
                    else:
                        time.sleep(TEST_RES_SEC)

                    stock_info_log_fg = True
                    ######## NEED FOR PORTING #########
                    # 実は一部約定していた場合もあるため、対処必要
                    order_work_data[symbolStr]["Status"] = HOLD_BUY

            elif judge_now == CANCEL_ORDER_SELL_CLOSE:
                if order_work_data[symbolStr]["Status"] == ORDER_SELL_CLOSE:
                    logger.info("<<Cancel SELL Close Order>>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 注文取消し
                        order_res = kabu_api.CancelOrder(order_work_data[symbolStr]["OrderId"])
                        if order_res == None:
                            logger.error("CancelOrder Error!!")
                            raise RuntimeError("CancelOrder Error!!")
                        if order_res.get("Result") != 0:
                            #注文取り消し失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            logger.info("Success Cancel Order id = %s",order_work_data[symbolStr]["OrderId"])
                    else:
                        time.sleep(TEST_RES_SEC)

                    stock_info_log_fg = True
                    ######## NEED FOR PORTING #########
                    # 実は一部約定していた場合もあるため、対処必要
                    order_work_data[symbolStr]["Status"] = HOLD_SELL

                
        elif now_datetime < FORCE_CLOSE_TIME: ##終わり間近の場合
            #全銘柄強制決済
            for symbol , symbol_order_data in order_work_data.items():
                if symbol_order_data["Status"] == HOLD_BUY:
                    #強制close注文
                    logger.info("<<Close BUY Position Order for "+ symbol +">>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 成り行きで発注 ******* 不成に変更 *********
                        order_res = kabu_api.OrderClose1dayMargin(symbol,order_info[symbol]["Order"]*ORDER_LOTS,candle_data.candle_close[0],False,auApi.OrderType.FUNARI_GOBA)
                        if order_res == None:
                            logger.error("OrderClose1dayMargin Error!!")
                            raise RuntimeError("OrderClose1dayMargin Error!!")
                        if order_res.get("Result") != 0:
                            #注文失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            order_id = order_res.get("OrderId")
                            symbol_order_data["OrderId"] = order_id
                            logger.info("Order id = %s",order_id)
                    else:
                        time.sleep(TEST_RES_SEC)
                        symbol_order_data["OrderId"] = "NotRealOrder"
                
                    symbol_order_data["Status"] = ORDER_BUY_CLOSE
                    symbol_order_data["ClosePrice"] = candle_data.candle_close[0]
                    symbol_order_data["ChkInt"] = OLDER_CHK_INTERBAL

                elif symbol_order_data["Status"] == HOLD_SELL:
                    logger.info("<<Close SELL Position Order for "+ symbol +">>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 成り行きで発注 ****** 不成に変更 ********
                        order_res = kabu_api.OrderClose1dayMargin(symbol,order_info[symbol]["Order"]*ORDER_LOTS,candle_data.candle_close[0],True,auApi.OrderType.FUNARI_GOBA)
                        if order_res == None:
                            logger.error("OrderClose1dayMargin Error!!")
                            raise RuntimeError("OrderClose1dayMargin Error!!")
                        if order_res.get("Result") != 0:
                            #注文失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            order_id = order_res.get("OrderId")
                            symbol_order_data["OrderId"] = order_id
                            logger.info("Order id = %s",order_id)
                    else:
                        time.sleep(TEST_RES_SEC)
                        symbol_order_data["OrderId"] = "NotRealOrder"

                    symbol_order_data["Status"] = ORDER_SELL_CLOSE
                    symbol_order_data["ClosePrice"] = candle_data.candle_close[0]
                    symbol_order_data["ChkInt"] = OLDER_CHK_INTERBAL
                
                elif symbol_order_data["Status"] == ORDER_BUY:
                    logger.info("<<Cancel BUY Position Order for "+ symbol +">>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 注文取消し
                        order_res = kabu_api.CancelOrder(symbol_order_data["OrderId"])
                        if order_res == None:
                            logger.error("CancelOrder Error!!")
                            raise RuntimeError("CancelOrder Error!!")
                        if order_res.get("Result") != 0:
                            #注文取り消し失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            logger.info("Success Cancel Order id = %s",symbol_order_data["OrderId"])
                    else:
                        time.sleep(TEST_RES_SEC)
                
                    symbol_order_data["Status"] == NO_ORDER_HOLD
                
                elif symbol_order_data["Status"] == ORDER_SELL:
                    logger.info("<<Cancel SELL Position Order for "+ symbol +">>")

                    if EXEC_REAL_ORDER_FG == True:
                        ## 注文取消し
                        order_res = kabu_api.CancelOrder(symbol_order_data["OrderId"])
                        if order_res == None:
                            logger.error("CancelOrder Error!!")
                            raise RuntimeError("CancelOrder Error!!")
                        if order_res.get("Result") != 0:
                            #注文取り消し失敗した場合、これ以降の処理はしない。
                            logger.warning("Order is not Success....")
                            logger.warning(" Result = %d",order_res.get("Result"))
                            return
                        else:
                            logger.info("Success Cancel Order id = %s",symbol_order_data["OrderId"])
                    else:
                        time.sleep(TEST_RES_SEC)
                
                    symbol_order_data["Status"] == NO_ORDER_HOLD
                # 注文発注中のものについては、不成(成り行き)で発注しているはずなので、対処不要

        else: ##いよいよ閉場間近になった時は、持っている建玉(プログラム管轄外も含む)を全決済
            pass
            #TODO この場合の処理はスレッド外で全銘柄決済にする

        return stock_info_log_fg