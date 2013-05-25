#! /usr/bin/env python

import SocketServer
import subprocess
import sys
import logging

from threading import Thread

class SingleTCPHandler(SocketServer.BaseRequestHandler):

    def handle(self):

        buffer = []

        while True:
            data = self.request.recv(1024)

            if not data:
                break
            else:
                buffer.append(data)

            buffer = "".join(buffer)
            buffer = buffer.split(" ")

            msg = buffer[0]

            self.request.send(msg)

        self.request.close()

class SimpleServer(SocketServer.ThreadingMixIn, SocketServer.TCPServer):

    daemon_threads = True
    allow_reuse_address = True

    def __init__(self, server_address, RequestHandlerClass):
        SocketServer.TCPServer.__init__(self, server_address, RequestHandlerClass)

if __name__ == "__main__":

    import ConfigParser
    import optparse

    parser = optparse.OptionParser()

    parser.add_option('--host', dest='host', action='store', default='127.0.0.1', help='...')
    parser.add_option('--port', dest='port', action='store', default=9999, help='...')
    parser.add_option('-v', '--verbose', dest='verbose', action='store_true', default=False, help='be chatty (default is false)')

    options, args = parser.parse_args()

    if options.verbose:
        logging.basicConfig(level=logging.DEBUG)
    else:
        logging.basicConfig(level=logging.INFO)

    logging.info("starting server at %s on port %s" % (options.host, options.port))

    server = SimpleServer((options.host, options.port), SingleTCPHandler)

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        sys.exit(0)
