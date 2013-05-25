#! /usr/bin/env python

import SocketServer
import subprocess
import sys
import logging
import json
import os.path

from threading import Thread

class SingleTCPHandler(SocketServer.BaseRequestHandler):

    def handle(self):

        buffer = []

	method = None
        path = None

        while True:

            data = self.request.recv(1024)

            if not data:
                break
            else:

                if not method or not path:
                    parts = data.split("\C")
                    method = parts[0]
                    path = parts[1]

                    if not method in ('PUT'):
                        logging.error("invalid method: %s" % method)
                        msg = json.dumps({'ok': 0, 'error': 'Invalid method'})
                        self.request.send(msg)
                        break

                    if ".." in path:
                        logging.error("invalid path: %s" % path)
                        msg = json.dumps({'ok': 0, 'error': 'Invalid path'})
                        self.request.send(msg)
                        break

                    data = "".join(parts[2:])

                if not data.endswith("\0"):
                    buffer.append(data)
                else:

                    buffer.append(data[:-1])

                    root = self.server.storage_root
                    abs_path = os.path.join(root, path)

                    try:

                        tree = os.path.dirname(abs_path)

                        if not os.path.exists(tree):
                            logging.debug("create %s" % tree)
                            os.makedirs(tree)

                        logging.debug("store %s" % abs_path)

                        fh = open(abs_path, 'wb')
                        fh.write("".join(buffer))
                        fh.close()

                        rsp = {'ok': 1, 'path': abs_path}

                    except Exception, e:
                        logging.error(e)
                        rsp = {'ok': 0, 'error': e}
                        
                    msg = json.dumps(rsp)
                    self.request.send(msg)

                    break

        self.request.close()

class Storagemaster(SocketServer.ThreadingMixIn, SocketServer.TCPServer):

    daemon_threads = True
    allow_reuse_address = True

    def __init__(self, server_address, storage_root, RequestHandlerClass):
        self.storage_root = storage_root
        SocketServer.TCPServer.__init__(self, server_address, RequestHandlerClass)

if __name__ == "__main__":

    import ConfigParser
    import optparse

    parser = optparse.OptionParser()

    parser.add_option('--host', dest='host', action='store', default='127.0.0.1', help='...')
    parser.add_option('--port', dest='port', action='store', default=9999, help='...')
    parser.add_option('-r', '--root', dest='root', action='store', default=None, help='...')
    parser.add_option('-v', '--verbose', dest='verbose', action='store_true', default=False, help='be chatty (default is false)')

    options, args = parser.parse_args()

    if options.verbose:
        logging.basicConfig(level=logging.DEBUG)
    else:
        logging.basicConfig(level=logging.INFO)

    if not options.root:
        logging.error("Invalid storage root")
        sys.exit()

    if not os.path.exists(options.root):
        logging.error("Storage root does not exist")
        sys.exit()

    logging.info("starting server at %s on port %s" % (options.host, options.port))

    server = Storagemaster((options.host, options.port), options.root, SingleTCPHandler)

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        logging.info("Shutting down")
        sys.exit(0)
    except Exception, e:
        logging.error(e)
        sys.exit()
