from http.server import BaseHTTPRequestHandler, HTTPServer
from CardServer import CardServer

class GetHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        message = handle(self.path)
        self.send_response(200)
        self.end_headers()
        self.wfile.write(message.encode('utf-8'))

    def log_message(self, format, *args):
        pass

def handle(path):
    if '?' in path:
        t = cs.evalurl(path)
        print (t)
        return t
    else:
        with open("manual.html", "r") as myfile:
            data = myfile.read()
        return data

cs = CardServer()
server = HTTPServer(('10.190.34.14', 80), GetHandler)
print('Starting server, use <Ctrl-C> to stop')
server.serve_forever()