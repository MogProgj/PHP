from ib_insync import *
ib = IB()
ib.connect('127.0.0.1', 4002, clientId=1)
print("Connected:", ib.isConnected())
print("Server time:", ib.reqCurrentTime())
ib.disconnect()
