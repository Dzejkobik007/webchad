import time
import requests
from requests.structures import CaseInsensitiveDict
from getpass import getpass
import json
from threading import Thread
import curses
from queue import Queue, Empty

host = "http://193.34.236.38:7777"

def login():
    username = input("Username:")
    password = getpass()
    return getToken(username, password)

def getToken(user, password):
    r = requests.post(url = host+"/user/login", data = {'username':user,'password':password})
    return json.loads(r.text)

def getRooms():
    headers = CaseInsensitiveDict()
    headers["Authorization"] = auth['token']
    r = requests.get(url = host+"/room", headers=headers)
    print(r.text)

def getMessages(roomid, limit):
    headers = CaseInsensitiveDict()
    headers["Authorization"] = auth['token']
    r = requests.get(url = host+"/room/"+str(roomid)+"/message?limit="+str(limit), headers=headers)
    return json.loads(r.text)

def sendMessage(message, roomid):
    headers = CaseInsensitiveDict()
    headers["Content-Type"] = "application/json"
    headers["Authorization"] = auth['token']
    data = json.dumps({'message': message})
    requests.put(url = host+"/room/"+str(roomid)+"/message", data=data, headers=headers)

def printMessages(result):
    nodenum = len(result)
    while nodenum:
        message = result[nodenum-1]['sender']+": "+result[nodenum-1]['text']+"\n"
        upperwin.addstr(message)
        nodenum-=1
        upperwin.refresh()


exitnum = 0

auth = login()
rooms = getRooms()
print(rooms)
roomid = input("Roomid:")
# roomid = 2

stdscr = curses.initscr()
stdscr.keypad(True)

upperwin = stdscr.subwin(8, 80, 0, 0)
lowerwin = stdscr.subwin(9,0)

def messageGatherThread():
    while True:
        upperwin.clear()
        try:
            result = getMessages(roomid, 7)
            if (len(result) > 0):
                printMessages(result)
            upperwin.refresh()
            upperwin.clear()
            time.sleep(1)
            if exitnum == 1:
                return
        except Empty:
            pass

# def outputThreadFunc():
#     upperwin.clear()
#     while True:
#         try:
#             inp = messageQueue.get(timeout=0.1)
#             if inp == 'exit':
#                 return
#             else:
#                 upperwin.addch('\n')
#                 upperwin.addstr(inp)
#         except Empty:
#             pass

def messageThreadFunc():
    global exitnum
    while True:
        lowerwin.addstr("->")
        command = lowerwin.getstr()
        if command:
            command = command.decode("utf-8")
            lowerwin.clear()
            lowerwin.refresh()
            if command == 'exit':
                exitnum = 1
                return
            if command == 'clear':
                upperwin.clear()
                return
            else:
                sendMessage(command, roomid)

outputThread = Thread(target=messageGatherThread)
inputThread = Thread(target=messageThreadFunc)

outputThread.start()
inputThread.start()
outputThread.join()
inputThread.join()
stdscr.keypad(False)
curses.endwin()
print("Exit")


