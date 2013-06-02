import urllib
import httplib
import base64
import json

class OAuth2:

    def __init__(self, access_token, host, **kwargs):

        self.access_token = access_token
        self.host = host

        self.endpoint = kwargs.get('endpoint', '/api/rest')

        # Strictly speaking not ever necessary until of course it
        # is like when I need to call a dev server or whatever.
        # This can safely be ignored most of the time.
        # (20130403/straup)

        self.username = kwargs.get('username', None)
        self.password = kwargs.get('password', None)

        # Same same - you should only ever talk to OAuth2 using HTTP
        # (20130403/straup)

        self.use_https = kwargs.get('use_https', True)

    def call (self, method, **kwargs):

        headers = {"Content-type": "application/x-www-form-urlencoded"}

        # See notes in __init__ (20130403/straup)

        if self.username and self.password:
            auth = base64.encodestring("%s:%s" % (self.username, self.password))
            auth = auth.strip()

            headers["Authorization"] = "Basic %s" % auth

        kwargs['method'] = method
        kwargs['format'] = 'json'
        kwargs['access_token'] = self.access_token

        body = urllib.urlencode(kwargs)

        if self.use_https:
            conn = httplib.HTTPSConnection(self.host)
        else:
            conn = httplib.HTTPConnection(self.host) 

        conn.request('POST', self.endpoint, body, headers)

        rsp = conn.getresponse()
        body = rsp.read()

        try:
            data = json.loads(body)
        except Exception, e:
            print body
            raise Exception, e

        # check status here...

        return data

if __name__ == '__main__':

    import sys
    import pprint
    import time
    import optparse

    parser = optparse.OptionParser(usage="python api.py --access-token <ACCESS TOKEN>")

    # sudo make me read a config file...

    parser.add_option('--access-token', dest='access_token',
                        help='Your (parallel-flickr) API access token',
                        action='store')

    parser.add_option('--endpoint', dest='endpoint',
                        help='The (parallel-flickr) API endpoint you\'re connecting to',
                        action='store', default=None)

    options, args = parser.parse_args()

    api = OAuth2(options.access_token, options.endpoint)

    try:
        now = int(time.time())

        rsp = api.call('api.test.echo', foo='bar', timestamp=now)
        print pprint.pformat(rsp)

    except Exception, e:
        print e

    sys.exit()
