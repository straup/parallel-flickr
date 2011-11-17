// namespacing!
if (!com) {
    var com = {};
}
if (!com.modestmaps) {
    com.modestmaps = {};
}

(function(MM) {

    var round = Math.round,
        floor = Math.floor,
        mmin = Math.min,
        mmax = Math.max;

    MM.NULL_PROVIDER = new MM.MapProvider(function(c) { return null; });

    /**
     * The GeoJSONProvider loads GeoJSON features for each tile and attaches
     * those as DOM element markers to to the corresponding tile <div/>.
     *
     * Markers are created by the buildMarker() method. You can override this in
     * a subclass or simply overwrite the instance method like so:
     *
     *  var provider = new GeoJSONProvider(...);
     *  provider.buildMarker = function(feature) {
     *      var marker = document.createElement("a"),
     *          name = feature.properties.name;
     *      marker.appendChild(document.createTextNode(name));
     *      return marker;
     *  };
     *
     * Using jQuery to build markers is recommended. Just remember to return the
     * actual DOM element reference, rather than the jQuery object:
     *
     *  provider.buildMarker = function(feature) {
     *      var marker = $("<a/>").text(feature.properties.name);
     *      ...
     *      return marker[0];
     *  };
     *
     * Markers are positioned relative to the tile and their coordinates can be
     * specified as percentages (when positionPercentages is true) or pixels
     * (when positionPercentages is false).
     *
     * GeoJSONProvider uses ModestMaps' CallbackManager for dispatching the
     * following events, both of which get an object with "element" and
     * "feature" properties mapped to the marker's corresponding DOM node and
     * GeoJSON feature.
     *
     * "addmarker": dispatched when a new marker is added or for each marker of
     * an already loaded tile that came back into view.
     *
     * "removemarker": dispatched for each marker on a tile when it is removed
     * from the DOM.
     */
    MM.GeoJSONProvider = function(template_provider, buildMarker) {
        MM.TilePaintingProvider.call(this, template_provider);
        this.callbackManager = new MM.CallbackManager(this, ["addmarker", "removemarker"]);
        this.cache = {};
        if (typeof buildMarker === "function") {
            this.buildMarker = buildMarker;
        }
    };

    MM.GeoJSONProvider.prototype = {
        // for remembering requests; FIXME: use the RequestManager?
        cache: null,
        useCache: false,

        // these aren't included in the prototype for some reason...
        tileWidth: 256,
        tileHeight: 256,

        // expose getTileUrl()
        getTileUrl: function(coord) {
            return this.template_provider.getTileUrl(coord);
        },

        // for dispatching "addmarker" and "removemarker" events
        callbackManager: null,
        // expose the callback interface
        addCallback: function(event, callback) {
            this.callbackManager.addCallback(event,callback);
        },
        removeCallback: function(event, callback) {
            this.callbackManager.removeCallback(event,callback);
        },
        dispatchCallback: function(event, message) {
            this.callbackManager.dispatchCallback(event,message);
        },

        // get a DOM node element for a given tile coordinate
        getTile: function(coord) {
            var key = coord.toKey();
            if (this.useCache && this.cache.hasOwnProperty(key)) {
                return this.cache[key].element;
            } else {
                var url = this.getTileUrl(coord); 
                if (!url) {
                    return null;
                }
                var item = {
                    coord: coord,
                    key: key,
                    url: url
                };
                var tile = document.createElement("div");
                tile.coord = coord;
                tile.setAttribute("class", "tile");
                tile.style.pointerEvents = "none";
                item.element = tile;
                this.cache[key] = item;

                var that = this;
                item.requestTimeout = setTimeout(function() {
                    // TODO: remove the jQuery dependency here
                    item.request = $.ajax(item.url, {
                        dataType: "json",
                        success: function(collection) {
                            if (item.aborted !== true) {
                                that.drawFeatures(collection, tile);
                            }
                            delete item.request;
                        },
                        error: function(message) {
                            that.drawError(message, tile);
                            delete item.request;
                        }
                    });
                }, 200);

                return tile;
            }
        },

        // default DOM node marker building function; override me!
        buildMarker: function(feature) {
            var marker = document.createElement("div");
            marker.setAttribute("class", "marker");
            marker.appendChild(document.createTextNode(feature.id || " "));
            return marker;
        },

        // get an DOM node marker for a given GeoJSON feature
        getFeatureMarker: function(feature) {
            var marker = this.buildMarker(feature);
            if (marker) {
                marker.style.pointerEvents = "all";
                marker.feature = feature;
            } else {
                // XXX: do something?
            }
            return marker;
        },

        // get the "location" of a GeoJSON feature
        getFeatureLocation: function(feature) {
            var geom = feature.geometry;
            switch (geom.type) {
                // Point geometries are converted to
                // com.modestmaps.Locations
                case "Point":
                    var lon = Number(geom.coordinates[0]),
                        lat = Number(geom.coordinates[1]);
                    return new MM.Location(lat, lon);

                // Polygon geometries are turned into the extent of their
                // first ring. In other words, the bounding box. These are
                // rendered as rectangular markers with an absolute
                // position, width and height.
                case "Polygon":
                    var ring = geom.coordinates[0],
                        len = ring.length,
                        north = Number.NEGATIVE_INFINITY,
                        south = Number.POSITIVE_INFINITY,
                        east = Number.NEGATIVE_INFINITY,
                        west = Number.POSITIVE_INFINITY;
                    for (var i = 0; i < len; i++) {
                        var c = ring[i],
                            lon = Number(c[0]),
                            lat = Number(c[1]);
                        north = mmax(north, lat);
                        south = mmin(south, lat);
                        east = mmax(east, lon);
                        west = mmin(west, lon);
                    }
                    return [
                        new MM.Location(north, west),
                        new MM.Location(south, east)
                    ];
            }
            return null;
        },


        // whether to position using percentages rather than pixels
        // (this will work better for fractional zooms)
        positionPercentages: true,
        getLocalPosition: function(loc, origin) {
            var coord = this.template_provider.locationCoordinate(loc).zoomTo(origin.zoom),
                pos = {coord: coord};
            // console.log([loc.lon, loc.lat], "->", coord.toKey(), "in", origin.toKey());
            pos.percent = {
                x: 100 * (coord.column - origin.column),
                y: 100 * (coord.row - origin.row)
            };
            pos.pixels = {
                x: this.tileWidth * (coord.column - origin.column),
                y: this.tileHeight * (coord.row - origin.row)
            };
            return pos;
        },

        // draw a GeoJSON FeatureCollection onto a given tile (a <div>)
        drawFeatures: function(collection, tile) {
            var features = collection.features,
                len = features.length,
                origin = tile.coord,
                markers = [];
            for (var i = 0; i < len; i++) {
                var feature = features[i],
                    marker = this.getFeatureMarker(feature);
                if (!marker) continue;

                var positioned = this.positionMarker(marker, feature, origin);
                if (!positioned) {
                    // XXX
                }

                tile.appendChild(marker);
                markers.push(marker);

                marker.feature = feature;
                this.dispatchCallback("addmarker", {feature: feature, element: marker, tile: tile});
            }
            tile.markers = markers;
        },

        // position a marker relative to the origin (tile) coordinate
        positionMarker: function(marker, feature, origin) {
            // console.log(feature.id, marker);
            marker.style.position = "absolute";
            var loc = this.getFeatureLocation(feature);
            if (loc instanceof Array) {
                var tl = this.getLocalPosition(loc[0], origin),
                    br = this.getLocalPosition(loc[1], origin);
                if (this.positionPercentages) {
                    var p = 2; // decimal precision
                    marker.style.left = (tl.percent.x).toFixed(p) + "%";
                    marker.style.top = (tl.percent.y).toFixed(p) + "%";
                    marker.style.width = (br.percent.x - tl.percent.x).toFixed(p) + "%";
                    marker.style.height = (br.percent.y - tl.percent.y).toFixed(p) + "%";
                } else {
                    marker.style.left = floor(tl.pixels.x) + "px";
                    marker.style.top = floor(tl.pixels.y) + "px";
                    marker.style.width = round(br.pixels.x - tl.pixels.x) + "px";
                    marker.style.height = round(br.pixels.y - tl.pixels.y) + "px";
                }
                return true;
            } else if (typeof loc === "object") {
                var pos = this.getLocalPosition(loc, origin);
                if (this.positionPercentages) {
                    marker.style.left = (pos.percent.x).toFixed(2) + "%";
                    marker.style.top = (pos.percent.y).toFixed(2) + "%";
                } else {
                    marker.style.left = round(pos.pixels.x) + "px";
                    marker.style.top = round(pos.pixels.y) + "px";
                }
                return true;
            } else {
                return false;
            }
        },

        // this is called when the AJAX request for a given tile fails.
        drawError: function(message, tile) {
            // console.log("error:", message.responseText);
            tile.setAttribute("class", "error");
            if (message && typeof message.responseText === "string") {
                tile.innerHTML = message.responseText;
                tile.style.overflow = "hidden";
            } else {
                /*
                console.log("ERROR: " + (typeof message) + "; " + message.toString());
                for (var p in message) {
                    if (message.hasOwnProperty(p) && typeof message[p] !== "function") {
                        console.log("  message[" + p + "]: " + message[p]);
                    }
                }
                */
            }
        },

        // release the tile
        releaseTile: function(coord) {
            var key = coord.toKey(),
                item = this.cache[key];
            if (item) {
                // console.log("[gjp] release cached item:", key, item);
                if (item.request) {
                    // console.log(item.request);
                    item.request.abort();
                    item.aborted = true;
                } else {
                    clearTimeout(item.requestTimeout);
                }
                if (item.element && item.element.markers && item.element.markers.length > 0) {
                    var that = this,
                        tile = item.tile;
                    item.element.markers.forEach(function(marker) {
                        that.dispatchCallback("removemarker", {element: marker, feature: marker.feature, tile: tile});
                    });
                }
                delete this.cache[key];
                return true;
            } else {
                return false;
            }
        },

        // reAddTile is called when an already loaded tile is re-added to
        // the DOM (via a hacked ModestMaps)
        reAddTile: function(key, coord, tile) {
            if (typeof tile.markers !== "undefined" && tile.markers.length > 0) {
                var item = {
                    element: tile,
                    coord: coord,
                    key: key,
                    url: this.template_provider.getTileUrl(coord)
                };
                this.cache[key] = item;
                // console.log("re-adding", tile.markers.length, "markers for", key);
                var that = this;
                tile.markers.forEach(function(marker) {
                    that.dispatchCallback("addmarker", {element: marker, feature: marker.feature, tile: tile});
                });
            }
        }
    };

    MM.extend(MM.GeoJSONProvider, MM.TilePaintingProvider);

    MM.MarkerLayer = function(map, provider, parent) {
        MM.Layer.call(this, map, provider || MM.NULL_PROVIDER, parent);

        // panning moves the container
        this.map.addCallback("panned", this.getPanned());
        // everything else repostitions all markers
        var zoomed = this.getZoomed();
        this.map.addCallback("zoomed", zoomed);
        this.map.addCallback("extentset",  zoomed);
        // TODO: this could probably be optimized to pan the container
        this.map.addCallback("centered",  zoomed);
        this.map.addCallback("resized",  zoomed);


        this.markers = [];
        this.resetPosition();
    };

    MM.MarkerLayer.prototype = {
        markers: null,

        clear: function() {
            while (this.markers.length > 0) {
                this.removeMarker(this.markers[0]);
            }
        },

        getLocation: function(loc) {
            switch (typeof loc) {
                case "string": {
                    return MM.Location.fromString(loc);
                }
                case "object": {
                    // GeoJSON
                    if (typeof loc.geometry === "object") {
                        return this.getFeatureLocation(loc);
                    }
                }
            }
            return loc;
        },
        getFeatureLocation: MM.GeoJSONProvider.prototype.getFeatureLocation,

        addMarker: function(marker, location) {
            if (!marker || !location) {
                return null;
            }
            marker.style.pointerEvents = "all";
            marker.style.position = "absolute";
            marker.location = this.getLocation(location);
            marker.coord = this.map.provider.locationCoordinate(marker.location);
            this.repositionMarker(marker);
            this.parent.appendChild(marker);
            this.markers.push(marker);
            return marker;
        },

        removeMarker: function(marker) {
            var index = this.markers.indexOf(marker);
            if (index > -1) {
                this.markers.splice(index, 1);
            }
            if (marker.parentNode == this.parent) {
                this.parent.removeChild(marker);
            }
            return marker;
        },

        position: null,
        resetPosition: function() {
            this.position = {x: 0, y: 0};
            this.parent.style.left = this.parent.style.top = "0px";
            var index = this.map.layers.indexOf(this);
            if (index > -1) {
                this.parent.style.zIndex = index;
            }
        },

        getZoomed: function() {
            if (!this._onZoomed) {
                var that = this;
                this._onZoomed = function(map, offset) {
                    that.onZoomed(map, offset);
                };
            }
            return this._onZoomed;
        },
        _onZoomed: null,
        onZoomed: function(map, offset) {
            this.resetPosition();
            this.updateMarkers();
        },

        repositionMarker: function(marker) {
            if (marker.coord) {
                var pos = this.map.coordinatePoint(marker.coord);
                marker.style.left = (pos.x >> 0) + "px";
                marker.style.top = (pos.y >> 0) + "px";
            }
        },

        updateMarkers: function() {
            var len = this.markers.length;
            for (var i = 0; i < len; i++) {
                this.repositionMarker(this.markers[i]);
            }
        },

        getPanned: function() {
            if (!this._onPanned) {
                var that = this;
                this._onPanned = function(map, offset) {
                    that.onPanned(map, offset);
                };
            }
            return this._onPanned;
        },
        _onPanned: null,
        onPanned: function(map, offset) {
            this.position.x += offset[0];
            this.position.y += offset[1];
            this.parent.style.left = (this.position.x >> 0) + "px";
            this.parent.style.top = (this.position.y >> 0) + "px";
        }
    };

    MM.extend(MM.MarkerLayer, MM.Layer);

    if (!Array.prototype.indexOf) {
        Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
            "use strict";
            if (this === void 0 || this === null) {
                throw new TypeError();
            }
            var t = Object(this);
            var len = t.length >>> 0;
            if (len === 0) {
                return -1;
            }
            var n = 0;
            if (arguments.length > 0) {
                n = Number(arguments[1]);
                if (n !== n) { // shortcut for verifying if it's NaN
                    n = 0;
                } else if (n !== 0 && n !== Infinity && n !== -Infinity) {
                    n = (n > 0 || -1) * Math.floor(Math.abs(n));
                }
            }
            if (n >= len) {
                return -1;
            }
            var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
            for (; k < len; k++) {
                if (k in t && t[k] === searchElement) {
                    return k;
                }
            }
            return -1;
        }
    }

})(com.modestmaps);
