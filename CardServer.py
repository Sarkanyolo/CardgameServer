import random, time
from urllib.parse import unquote

class user():
    def __init__(self, nick):
        self.nick = nick
        self.duel = '-'
        self.lastupdate = int(time.time())
        self.generateId()

    def generateId(self):
        self.id = ''.join(random.choice('123456789abcdefghijklmnopqrstuwxyz') for i in range(9))

    def __lt__(self, other):
         return self.nick < other.nick



class message():
    def __init__(self, id, message):
        self.id = id
        self.message = message


class game():
    def __init__(self, nick1, nick2, guess1, guess2, score1, score2):
        self.nick1 = nick1
        self.nick2 = nick2
        self.guess1 = guess1
        self.guess2 = guess2
        self.score1 = score1
        self.score2 = score2

class CardServer():
    def __init__(self):
        self.users = list()
        self.messages = list()
        self.games = list()

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
        if len(nick)<3: return 'NOK'
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
        self.users = [i for i in self.users if i.id != id]
        for i in self.messages:
            if i.id == id: i.id = '-'
        self.__clean()
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
            nick = self.__getNick(id)
            t = message(self.__getId(op), 'Start#' + nick)
            self.messages.append(t)

            # Add the to games
            t = game(nick, op, 0, 0, 0, 0)
            self.games.append(t)

            # Clean duels
            for i in self.users:
                if i.nick == op:
                    i.duel = '-'
                    break
            return 'OK'
        return 'NOK'

    def sduel(self, op, id):
        self.__clean()
        if op not in [i.nick for i in self.users]: return 'DISC'
        if op in [i.nick1 for i in self.games] + [i.nick2 for i in self.games]: return 'PLAY'
        for e in self.users:
            if e.nick == op:
                if e.duel != '-': return 'DUEL'
                else:
                    for t in self.users:
                        if t.id == id:
                            t.duel = op
                            return 'OK'

    def sguess(self, n, id):
        nick = self.__getNick(id)
        flag = False
        for i in self.games:
            if i.nick1 == nick:
                i.guess1 = n
                flag = True
            elif i.nick2 == nick:
                i.guess2 = n
                flag = True
            if flag:
                if i.guess1 > 0 and i.guess2 > 0:
                    pass
                    # Logic needs to be implemented here
                return 'OK'
        return 'NOK'

    def swhisp(self, mess, id, op):
        if op not in [i.nick for i in self.users]:
            return 'DISC'
        t = message(self.__getId(op), 'Whisper#' + self.__getNick(id) + '#' + unquote(mess))
        self.messages.append(t)
        return 'OK'

    def dduel(self, id):
        for e in self.users:
            if e.id == id:
                e.duel = '-'
                return 'OK'

    def gstatus(self, id):
        if id not in [i.id for i in self.users]: return 'DISC'
        for i in self.users:
            if i.id == id:
                i.lastupdate = int(time.time())
                break
        duels = list()
        nick = self.__getNick(id)
        for i in self.users:
            if i.duel == nick: duels.append(i.nick)
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
            return self.sduel(data[command], data['me'])
        if command == 'sname':
            return self.sname(data[command])
        if command == 'sguess':
            return self.sguess(data[command], data['me'])
        if command == 'glist':
            return self.glist()
        if command == 'swhisp':
            return self.swhisp(data[command], data['me'], data['to'])
        return 'Command not exists'