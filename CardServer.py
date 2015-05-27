import time
from urllib.parse import unquote
from random import choice, randint

class user():
    def __init__(self, nick):
        self.nick = nick
        self.duel = '-'
        self.lastupdate = int(time.time())
        self.generateId()

    def generateId(self):
        self.id = ''.join(choice('123456789abcdefghijklmnopqrstuwxyz') for i in range(7))

    def __lt__(self, other):
         return self.nick < other.nick


class message():
    def __init__(self, id, message):
        self.id = id
        self.message = message


class game():
    def __init__(self, nick1, nick2):
        self.nick1 = nick1
        self.nick2 = nick2
        self.guess1 = 0
        self.guess2 = 0
        self.score1 = 0
        self.score2 = 0

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

    def __calc_winner(self, cpu, p1, p2):
        delta1 = abs(p1-cpu)
        delta2 = abs(p2-cpu)
        if delta1 == delta2: return 0
        return 1 if delta1 < delta2 else 2

    def __compute_game(self):
        for g in self.games:
            if g.guess1 > 0 and g.guess2 > 0:
                p1id = self.__getId(g.nick1)
                p2id = self.__getId(g.nick2)
                cpu = randint(1,9999)
                res = self.__calc_winner(cpu, g.guess1, g.guess2)
                # DRAW
                if res == 0:
                    g.score1 += 1
                    g.score2 += 1
                    t = message(p1id, 'Number#' + str(cpu) + '#' + str(g.guess2) + '#DRAW#' + str(g.score1) + '#' + str(g.score2))
                    self.messages.append(t)
                    t = message(p2id, 'Number#' + str(cpu) + '#' + str(g.guess1) + '#DRAW#' + str(g.score2) + '#' + str(g.score1))
                    self.messages.append(t)
                # First player won
                elif res == 1:
                    g.score1 += 1
                    t = message(p1id, 'Number#' + str(cpu) + '#' + str(g.guess2) + '#WIN#' + str(g.score1) + '#' + str(g.score2))
                    self.messages.append(t)
                    t = message(p2id, 'Number#' + str(cpu) + '#' + str(g.guess1) + '#LOSE#' + str(g.score2) + '#' + str(g.score1))
                    self.messages.append(t)
                # Second player won
                else:
                    g.score2 += 1
                    t = message(p1id, 'Number#' + str(cpu) + '#' + str(g.guess2) + '#LOSE#' + str(g.score1) + '#' + str(g.score2))
                    self.messages.append(t)
                    t = message(p2id, 'Number#' + str(cpu) + '#' + str(g.guess1) + '#WIN#' + str(g.score2) + '#' + str(g.score1))
                    self.messages.append(t)

                # Reset guesses
                g.guess1 = 0
                g.guess2 = 0

                # Check if game ended
                endgame = False
                # Ended with DRAW
                if g.score1 > 9 and g.score2 > 9:
                    self.messages.append(message(p1id, 'EndGame#DRAW'))
                    self.messages.append(message(p2id, 'EndGame#DRAW'))
                    endgame = True
                # Ended with p1 win
                elif g.score1 > 9:
                    self.messages.append(message(p1id, 'EndGame#WIN'))
                    self.messages.append(message(p2id, 'EndGame#LOSE'))
                    endgame = True
                # Ended with p2 win
                elif g.score2 > 9:
                    self.messages.append(message(p1id, 'EndGame#LOSE'))
                    self.messages.append(message(p2id, 'EndGame#WIN'))
                    endgame = True

                # Delete ended games
                if endgame:
                    g.nick1 = '-'
                    g.nick2 = '-'


    def __clean(self):
        # Cache time before process
        t = int(time.time())

        # Check if user deletion needed
        need_del = False
        for i in self.users:
            if i.lastupdate < t-30:
                need_del = True
                break
        if need_del:
            # Change message id to '-', when the user will be deleted
            for i in [i.id for i in self.users if i.lastupdate <= t - 30]:
                for m in self.messages:
                    if m.id == i: m.id = '-'
            # Delete user
            self.users = [i for i in self.users if i.lastupdate >= t - 30]

        # Check if message deletion needed
        need_del = False
        for i in self.messages:
            if i.id == '-':
                need_del = True
                break
        # Delete unneeded messages
        if need_del: self.messages = [i for i in self.messages if i.id != '-']

        # Check if game deletion needed
        need_del = False
        for i in self.games:
            if i.nick1 == '-' or i.nick2 == '-':
                need_del = True
                break

        # Clean games
        if need_del: self.games = [i for i in self.games if i.nick1 != '-' and i.nick2 != '-']

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
        # Delete user from self.users
        self.users = [i for i in self.users if i.id != id]
        # Delete user messages
        for i in self.messages:
            if i.id == id: i.id = '-'
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
                    self.messages.append(message(i.id, 'DuelNOK'))
                    return 'OK'
            return 'OK'
        if answer == 'YES':
            # Check if disconnected
            if op not in [i.nick for i in self.users]: return 'NOK'

            # Send start messages
            self.messages.append(message(id, 'Start#'+op))
            nick = self.__getNick(id)
            self.messages.append(message(self.__getId(op), 'Start#' + nick))

            # Add the to games
            self.games.append(game(nick, op))

            # Clean duels
            for i in self.users:
                if i.nick == op:
                    i.duel = '-'
                    break
            return 'OK'

        # For undefined answers
        return 'NOK'

    def sduel(self, op, id):
        # Check whether user is disconnected
        self.__clean()
        if op not in [i.nick for i in self.users]: return 'DISC'

        # Check if user is playing
        if op in [i.nick1 for i in self.games] or op in [i.nick2 for i in self.games]: return 'PLAY'

        # Search for opponent
        for e in self.users:
            if e.nick == op:
                # When opponent found, check if dueling
                if e.duel != '-': return 'DUEL'
                # OP exist and free, ask for duel
                for t in self.users:
                    if t.id == id:
                        t.duel = op
                        return 'OK'

        # Should not happen; id is not found in self.users
        # No need to handle, gstatus will send DISC signal
        return 'NOK'

    def sguess(self, n, id):
        nick = self.__getNick(id)
        flag = False
        for i in self.games:
            if i.nick1 == nick:
                i.guess1 = int(n)
                flag = True
            elif i.nick2 == nick:
                i.guess2 = int(n)
                flag = True
            if flag:
                self.__compute_game()
                return 'OK'
        return 'NOK'

    def swhisp(self, mess, id, op):
        # Check if whisper's destination is disconnected
        if op not in [i.nick for i in self.users]: return 'DISC'
        # Send whisper
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
        s = url[url.index('?')+1:]
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