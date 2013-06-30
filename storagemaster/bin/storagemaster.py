#! /usr/bin/env python

import SocketServer
import subprocess
import sys
import logging
import json
import os
import os.path
import select

from threading import Thread

class StoragemasterHandler(SocketServer.BaseRequestHandler):

    def handle(self):

        buffer = []
        
	method = None
        path = None
        length = None

        bytes_in = 1024		# weird stuff happens if you make this 2048... (20130529/straup)
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

                    if not method in ('PUT', 'GET', 'EXISTS', 'DELETE'):
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

        if not error and method == 'PUT' and bytes_rcvd != length:
            error = "Data length mis-match - got %s expected %s" % (bytes_rcvd, length)

        if error:
            logging.error(error)
            self.request.send(str(0))
            self.request.send(error)
            self.request.close()
            return

        root = self.server.storage_root
        abs_path = os.path.join(root, path)

        logging.debug("%s %s" % (method, abs_path))

        # I don't really have much of an opinion about the
        # JSON stuff. It's just easy right now (20130629/straup)

        try:

            if method == 'EXISTS':

                if not os.path.exists(abs_path):
                    rsp = {'ok': 0, 'error': 'file not found'}
                else:
                    rsp = {'ok': 1, 'body': abs_path}

            elif method == 'GET':

                if not os.path.exists(abs_path):
                    rsp = {'ok': 0, 'error': 'file not found'}
                else:
                    fh = open(abs_path, 'rb')
                    body = fh.read()
                    fh.close()

                    rsp = {'ok': 1, 'body': body}

            elif method == 'PUT':

                tree = os.path.dirname(abs_path)

                if not os.path.exists(tree):
                    os.makedirs(tree)

                buffer = "".join(buffer)

                fh = open(abs_path, 'wb')
                fh.write(buffer)
                fh.close()

                rsp = {'ok': 1, 'body': abs_path}

            elif method == 'DELETE':

                if not os.path.exists(abs_path):
                    rsp = {'ok': 0, 'error': 'file not found'}
                else:
                    os.unlink(abs_path)
                    rsp = {'ok': 1, 'body': abs_path}

            else:
                raise Exception, "Why are you here"

        except Exception, e:
            logging.error(e)
            rsp = {'ok': 0, 'error': str(e)}

        self.request.setblocking(1)
        self.request.send(str(rsp['ok']))

        if rsp['ok']:
            self.request.send(rsp.get('body', ''))
        else:
            self.request.send(rsp.get('error', 'SNFU'))

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
