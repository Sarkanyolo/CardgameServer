import random, time

class user():
    def __init__(self, nick):
        self.nick = nick
        self.duel = '-'
        self.lastupdate = int(time.time())
        self.generateId()

    def generateId(self):
        self.id = ''.join(random.choice('abcdefghijklmnopqrstuwxyz') for i in range(32))

    def __lt__(self, other):
         return self.nick < other.nick



class message():
    def __init__(self, id, message):
        self.id = id
        self.message = message



class CardServer():
    def __init__(self):
        self.users = list()
        self.messages = list()

    def __getNick(self,id):
        for i in self.users:
            if i.id == id: return i.nick

    def __getId(self, nick):
        for i in self.users:
            if i.nick == nick: return i.id

    def __clean(self):
        t = int(time.time())
        flag = False
        for i in self.users:
            if i.lastupdate < t-30:
                flag = True
                break
        if flag: self.users = [i for i in self.users if i.lastupdate > t - 31]
        flag = False
        for i in self.messages:
            if i.id == '-':
                flag = True
                break
        if flag: self.messages = [i for i in self.messages if i.id != '-']

    def sname(self, nick):
        self.__clean()
        for i in self.users:
            if i.nick == nick: return 'NOK'
        t = user(nick)
        while t.id in [i.id for i in self.users]:
            t.generateId()
        self.users.append(t)
        self.users.sort()
        return t.id

    def dname(self, id):
        self.__clean()
        self.users = [i for i in self.users if i.id != id]
        return 'OK'

    def glist(self):
        t = list()
        for i in self.users:
            t.append(i.nick)
        return '#'.join(t)

    def aduel(self, answer, id, op):
        if answer == 'NO':
            for i in self.users:
                if i.nick == op:
                    i.duel = '-'
                    t = message(i.id, 'DuelNOK')
                    self.messages.append(t)
                    return 'OK'
            return 'OK'
        if answer == 'YES':
            if op not in [i.nick for i in self.users]: return 'NOK'
            t = message(id, 'Start#'+op)
            self.messages.append(t)
            t = message(self.__getId(op), 'Start#'+self.__getNick(id))
            self.messages.append(t)
            for i in self.users:
                if i.nick == op:
                    i.duel = '-'
                    break
            return 'OK'
        return 'NOK'

    def sduel(self, id, op):
        self.__clean()
        if op not in [i.nick for i in self.users]: return 'DISC'
        for e in self.users:
            if e.nick == op:
                if e.duel != '-': return 'DUEL'
                else:
                    for t in self.users:
                        if t.id == id:
                            t.duel = op
                            return 'OK'

    def dduel(self, id):
        for e in self.users:
            if e.id == id:
                e.duel = '-'
                return 'OK'

    def gstatus(self, id):
        if id not in [i.id for i in self.users]:
            return 'DISC'
        for i in self.users:
            if i.id == id:
                i.lastupdate = int(time.time())
                break
        duels = list()
        nick = self.__getNick(id)
        for i in self.users:
            if i.duel == nick:
                duels.append(i.nick)
        if len(duels)>0: return 'Duel#' + '#'.join(duels)
        for i in self.messages:
            if i.id == id:
                t = i.message
                i.id = '-'
                return t
        return 'OK'

    def evalurl(self, url):
        data = {}
        s = url.split('?')[1]
        print(s, end=' -> ')
        for i in s.split('&'):
            j=i.split('=')
            data[j[0]] = j[1]
        command = s.split('=')[0]
        if command == 'gstatus':
            return self.gstatus(data[command])
        if command == 'aduel':
            return self.aduel(data[command], data['me'], data['op'])
        if command == 'dduel':
            return self.dduel(data[command])
        if command == 'dname':
            return self.dname(data[command])
        if command == 'sduel':
            return self.sduel(data[command], data['op'])
        if command == 'sname':
            return self.sname(data[command])
        if command == 'glist':
            return self.glist()
        return 'Command not exists'