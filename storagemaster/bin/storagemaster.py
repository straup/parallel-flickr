#! /usr/bin/env python

import SocketServer
import subprocess
import sys
import logging
import json
import os.path
import select

from threading import Thread

class StoragemasterHandler(SocketServer.BaseRequestHandler):

    def handle(self):

        buffer = []
        
	method = None
        path = None
        length = None

        bytes_in = 2048
        bytes_rcvd = 0

        error = None

	# http://stackoverflow.com/questions/2719017/how-to-set-timeout-on-pythons-socket-recv-method
        self.request.setblocking(0)
        timeout = 10

        while True:

            ready = select.select([self.request], [], [], timeout)

            if ready[0]:
                data = self.request.recv(bytes_in)

            if not data:
                logging.debug("no more data")
                break

            else:

                # logging.debug("processing %s bytes" % len(data))

                if not method or not path:
                    parts = data.split("\C")
                    
                    method = parts[0]
                    path = parts[1]

                    if not method in ('PUT', 'EXISTS'):
                        error = "Invalid method"
                        break

                    if not path:
                        error = "Missing path"
                        break

                    if ".." in path:
                        error = "Invalid path"
                        break

                    if method != 'PUT':
                        break

                    length = int(parts[2])

                    if not length:
                        error = "Missing file length"
                        break
                            
                    # logging.debug("%s %s @ %s bytes" % (method, path, length))                        
                    data = "".join(parts[3:])

                buffer.append(data)

                bytes_rcvd += len(data)
                # logging.debug("%s %s bytes of %s" % (path, bytes_rcvd, length))

                if bytes_rcvd == length:
                    break

                if bytes_rcvd > length:
                    error = "Too much data"
                    break

        if method == 'PUT' and bytes_rcvd != length:
            error = "Data length mis-match"

        if error:
            logging.error(error)
            msg = json.dumps({'ok': 0, 'error': error})
            self.request.send(msg)
            self.request.close()
            return

        #

        root = self.server.storage_root
        abs_path = os.path.join(root, path)

        logging.debug("%s %s" % (method, abs_path))

        # See the way we're returning JSON? That may change yet specifically
        # to handle GET requests (20130527/straup)
        
        msg = {}

        try:

            if method == 'EXISTS':

                if os.path.exists(abs_path):
                    rsp = {'ok': 1, 'path': abs_path}
                else:
                    rsp = {'ok': 0, 'path': abs_path}

            elif method == 'PUT':

                tree = os.path.dirname(abs_path)

                if not os.path.exists(tree):
                    os.makedirs(tree)

                buffer = "".join(buffer)

                fh = open(abs_path, 'wb')
                fh.write(buffer)
                fh.close()

                rsp = {'ok': 1, 'path': abs_path}
            else:
                raise Exception, "Why are you here"

        except Exception, e:
            logging.error(e)
            rsp = {'ok': 0, 'error': str(e)}

        msg = json.dumps(rsp)
        self.request.send(msg)

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

    server = Storagemaster((options.host, int(options.port)), options.root, StoragemasterHandler)

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        logging.info("Shutting down")
        sys.exit(0)
    except Exception, e:
        logging.error(e)
        sys.exit()
