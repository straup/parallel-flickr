/*!
 * Modest Maps JS v1.0.0
 * http://modestmaps.com/
 *
 * Copyright (c) 2011 Stamen Design, All Rights Reserved.
 *
 * Open source under the BSD License.
 * http://creativecommons.org/licenses/BSD/
 *
 * Versioned using Semantic Versioning (v.major.minor.patch)
 * See CHANGELOG and http://semver.org/ for more details.
 *
 */

var previousMM = MM;

// namespacing for backwards-compatibility
if (!com) {
    var com = {};
    if (!com.modestmaps) com.modestmaps = {};
}

var MM = com.modestmaps = {
  noConflict: function() {
    MM = previousMM;
    return this;
  }
};

(function(MM) {
    // Make inheritance bearable: clone one level of properties
    MM.extend = function(child, parent) {
        for (var property in parent.prototype) {
            if (typeof child.prototype[property] == "undefined") {
                child.prototype[property] = parent.prototype[property];
            }
        }
        return child;
    };

    MM.getFrame = function () {
        // native animation frames
        // http://webstuff.nfshost.com/anim-timing/Overview.html
        // http://dev.chromium.org/developers/design-documents/requestanimationframe-implementation
        // http://paulirish.com/2011/requestanimationframe-for-smart-animating/
        // can't apply these directly to MM because Chrome needs window
        // to own webkitRequestAnimationFrame (for example)
        // perhaps we should namespace an alias onto window instead? 
        // e.g. window.mmRequestAnimationFrame?
        return function(callback) {
            (window.requestAnimationFrame  ||
            window.webkitRequestAnimationFrame ||
            window.mozRequestAnimationFrame    ||
            window.oRequestAnimationFrame      ||
            window.msRequestAnimationFrame     ||
            function (callback) {
                window.setTimeout(function () {
                    callback(+new Date());
                }, 10);
            })(callback);
        };
    }();

    // Inspired by LeafletJS
    MM.transformProperty = (function(props) {
        if (!this.document) return; // node.js safety
        var style = document.documentElement.style;
        for (var i = 0; i < props.length; i++) {
            if (props[i] in style) {
                return props[i];
            }
        }
        return false;
    })(['transformProperty', 'WebkitTransform', 'OTransform', 'MozTransform', 'msTransform']);

    MM.matrixString = function(point) {
        // Make the result of point.scale * point.width a whole number.
        if (point.scale * point.width % 1) {
            point.scale += (1 - point.scale * point.width % 1) / point.width;
        }

        if (MM._browser.webkit3d) {
            return 'matrix3d(' +
                [(point.scale || '1'), '0,0,0,0',
                 (point.scale || '1'), '0,0',
                '0,0,1,0',
                (point.x + (((point.width  * point.scale) - point.width) / 2)).toFixed(4),
                (point.y + (((point.height * point.scale) - point.height) / 2)).toFixed(4),
                0,1].join(',') + ')';
        } else {
            var unit = (MM.transformProperty == 'MozTransform') ? 'px' : '';
            return 'matrix(' +
                [(point.scale || '1'), 0, 0,
                (point.scale || '1'),
                (point.x + (((point.width  * point.scale) - point.width) / 2)) + unit,
                (point.y + (((point.height * point.scale) - point.height) / 2)) + unit
                ].join(',') + ')';
        }
    };

    MM._browser = (function(window) {
        return {
            webkit: ('WebKitCSSMatrix' in window),
            webkit3d: ('WebKitCSSMatrix' in window) && ('m11' in new WebKitCSSMatrix())
        };
    })(this); // use this for node.js global

    MM.moveElement = function(el, point) {
        if (MM.transformProperty) {
            // Optimize for identity transforms, where you don't actually
            // need to change this element's string. Browsers can optimize for
            // the .style.left case but not for this CSS case.
            var ms = MM.matrixString(point);
            if (el[MM.transformProperty] !== ms) {
                el.style[MM.transformProperty] =
                    el[MM.transformProperty] = ms;
            }
        } else {
            el.style.left = point.x + 'px';
            el.style.top = point.y + 'px';
            el.style.width =  Math.ceil(point.width  * point.scale) + 'px';
            el.style.height = Math.ceil(point.height * point.scale) + 'px';
        }
    };

    // Events
    // Cancel an event: prevent it from bubbling
    MM.cancelEvent = function(e) {
        // there's more than one way to skin this cat
        e.cancelBubble = true;
        e.cancel = true;
        e.returnValue = false;
        if (e.stopPropagation) { e.stopPropagation(); }
        if (e.preventDefault) { e.preventDefault(); }
        return false;
    };

    // From underscore.js
    MM.bind = function(func, obj) {
        var slice = Array.prototype.slice;
        var nativeBind = Function.prototype.bind;
        if (func.bind === nativeBind && nativeBind) {
            return nativeBind.apply(func, slice.call(arguments, 1));
        }
        var args = slice.call(arguments, 2);
        return function() {
          return func.apply(obj, args.concat(slice.call(arguments)));
        };
    };

    // see http://ejohn.org/apps/jselect/event.html for the originals
    MM.addEvent = function(obj, type, fn) {
        if (obj.addEventListener) {
            obj.addEventListener(type, fn, false);
            if (type == 'mousewheel') {
                obj.addEventListener('DOMMouseScroll', fn, false);
            }
        } else if (obj.attachEvent) {
            obj['e'+type+fn] = fn;
            obj[type+fn] = function(){ obj['e'+type+fn](window.event); };
            obj.attachEvent('on'+type, obj[type+fn]);
        }
    };

    MM.removeEvent = function( obj, type, fn ) {
        if (obj.removeEventListener) {
            obj.removeEventListener(type, fn, false);
            if (type == 'mousewheel') {
                obj.removeEventListener('DOMMouseScroll', fn, false);
            }
        } else if (obj.detachEvent) {
            obj.detachEvent('on'+type, obj[type+fn]);
            obj[type+fn] = null;
        }
    };

    // Cross-browser function to get current element style property
    MM.getStyle = function(el,styleProp) {
        if (el.currentStyle)
            return el.currentStyle[styleProp];
        else if (window.getComputedStyle)
            return document.defaultView.getComputedStyle(el,null).getPropertyValue(styleProp);
    };
    // Point
    MM.Point = function(x, y) {
        this.x = parseFloat(x);
        this.y = parseFloat(y);
    };

    MM.Point.prototype = {
        x: 0,
        y: 0,
        toString: function() {
            return "(" + this.x.toFixed(3) + ", " + this.y.toFixed(3) + ")";
        },
        copy: function() {
            return new MM.Point(this.x, this.y);
        }
    };

    // Get the euclidean distance between two points
    MM.Point.distance = function(p1, p2) {
        var dx = (p2.x - p1.x);
        var dy = (p2.y - p1.y);
        return Math.sqrt(dx*dx + dy*dy);
    };

    // Get a point between two other points, biased by `t`.
    MM.Point.interpolate = function(p1, p2, t) {
        var px = p1.x + (p2.x - p1.x) * t;
        var py = p1.y + (p2.y - p1.y) * t;
        return new MM.Point(px, py);
    };
    // Coordinate
    // ----------
    // An object representing a tile position, at as specified zoom level.
    // This is not necessarily a precise tile - `row`, `column`, and
    // `zoom` can be floating-point numbers, and the `container()` function
    // can be used to find the actual tile that contains the point.
    MM.Coordinate = function(row, column, zoom) {
        this.row = row;
        this.column = column;
        this.zoom = zoom;
    };

    MM.Coordinate.prototype = {

        row: 0,
        column: 0,
        zoom: 0,

        toString: function() {
            return "("  + this.row.toFixed(3) +
                   ", " + this.column.toFixed(3) +
                   " @" + this.zoom.toFixed(3) + ")";
        },
        // Quickly generate a string representation of this coordinate to
        // index it in hashes. 
        toKey: function() {
            // We've tried to use efficient hash functions here before but we took
            // them out. Contributions welcome but watch out for collisions when the
            // row or column are negative and check thoroughly (exhaustively) before
            // committing.
            return [ this.zoom, this.row, this.column ].join(',');
        },
        // Clone this object.
        copy: function() {
            return new MM.Coordinate(this.row, this.column, this.zoom);
        },
        // Get the actual, rounded-number tile that contains this point.
        container: function() {
            // using floor here (not parseInt, ~~) because we want -0.56 --> -1
            return new MM.Coordinate(Math.floor(this.row),
                                     Math.floor(this.column),
                                     Math.floor(this.zoom));
        },
        // Recalculate this Coordinate at a different zoom level and return the
        // new object.
        zoomTo: function(destination) {
            var power = Math.pow(2, destination - this.zoom);
            return new MM.Coordinate(this.row * power,
                                     this.column * power,
                                     destination);
        },
        // Recalculate this Coordinate at a different relative zoom level and return the
        // new object.
        zoomBy: function(distance) {
            var power = Math.pow(2, distance);
            return new MM.Coordinate(this.row * power,
                                     this.column * power,
                                     this.zoom + distance);
        },
        // Move this coordinate up by `dist` coordinates
        up: function(dist) {
            if (dist === undefined) dist = 1;
            return new MM.Coordinate(this.row - dist, this.column, this.zoom);
        },
        // Move this coordinate right by `dist` coordinates
        right: function(dist) {
            if (dist === undefined) dist = 1;
            return new MM.Coordinate(this.row, this.column + dist, this.zoom);
        },
        // Move this coordinate down by `dist` coordinates
        down: function(dist) {
            if (dist === undefined) dist = 1;
            return new MM.Coordinate(this.row + dist, this.column, this.zoom);
        },
        // Move this coordinate left by `dist` coordinates
        left: function(dist) {
            if (dist === undefined) dist = 1;
            return new MM.Coordinate(this.row, this.column - dist, this.zoom);
        }
    };
    // Location
    // --------
    MM.Location = function(lat, lon) {
        this.lat = parseFloat(lat);
        this.lon = parseFloat(lon);
    };

    MM.Location.prototype = {
        lat: 0,
        lon: 0,
        toString: function() {
            return "(" + this.lat.toFixed(3) + ", " + this.lon.toFixed(3) + ")";
        },
        copy: function() {
            return new MM.Location(this.lat, this.lon);
        }
    };

    // returns approximate distance between start and end locations
    //
    // default unit is meters
    //
    // you can specify different units by optionally providing the
    // earth's radius in the units you desire
    //
    // Default is 6,378,000 metres, suggested values are:
    //
    // * 3963.1 statute miles
    // * 3443.9 nautical miles
    // * 6378 km
    //
    // see [Formula and code for calculating distance based on two lat/lon locations](http://jan.ucc.nau.edu/~cvm/latlon_formula.html)
    MM.Location.distance = function(l1, l2, r) {
        if (!r) {
            // default to meters
            r = 6378000;
        }
        var deg2rad = Math.PI / 180.0,
            a1 = l1.lat * deg2rad,
            b1 = l1.lon * deg2rad,
            a2 = l2.lat * deg2rad,
            b2 = l2.lon * deg2rad,
            c = Math.cos(a1) * Math.cos(b1) * Math.cos(a2) * Math.cos(b2),
            d = Math.cos(a1) * Math.sin(b1) * Math.cos(a2) * Math.sin(b2),
            e = Math.sin(a1) * Math.sin(a2);
        return Math.acos(c + d + e) * r;
    };

    // Interpolates along a great circle, f between 0 and 1
    //
    // * FIXME: could be heavily optimized (lots of trig calls to cache)
    // * FIXME: could be inmproved for calculating a full path
    MM.Location.interpolate = function(l1, l2, f) {
        if (l1.lat === l2.lat && l1.lon === l2.lon) {
            return new MM.Location(l1.lat, l1.lon);
        }
        var deg2rad = Math.PI / 180.0,
            lat1 = l1.lat * deg2rad,
            lon1 = l1.lon * deg2rad,
            lat2 = l2.lat * deg2rad,
            lon2 = l2.lon * deg2rad;

        var d = 2 * Math.asin(
            Math.sqrt(
              Math.pow(Math.sin((lat1 - lat2) / 2), 2) +
              Math.cos(lat1) * Math.cos(lat2) *
              Math.pow(Math.sin((lon1 - lon2) / 2), 2)));
        var bearing = Math.atan2(
            Math.sin(lon1 - lon2) *
            Math.cos(lat2),
            Math.cos(lat1) *
            Math.sin(lat2) -
            Math.sin(lat1) *
            Math.cos(lat2) *
            Math.cos(lon1 - lon2)
        )  / -(Math.PI / 180);

        bearing = bearing < 0 ? 360 + bearing : bearing;

        var A = Math.sin((1-f)*d)/Math.sin(d);
        var B = Math.sin(f*d)/Math.sin(d);
        var x = A * Math.cos(lat1) * Math.cos(lon1) +
          B * Math.cos(lat2) * Math.cos(lon2);
        var y = A * Math.cos(lat1) * Math.sin(lon1) +
          B * Math.cos(lat2) * Math.sin(lon2);
        var z = A * Math.sin(lat1) + B * Math.sin(lat2);

        var latN = Math.atan2(z, Math.sqrt(Math.pow(x, 2) + Math.pow(y, 2)));
        var lonN = Math.atan2(y,x);

        return new MM.Location(latN / deg2rad, lonN / deg2rad);
    };

    // MapExtent
    // ----------
    // An object representing a map's rectangular extent, defined by its north,
    // south, east and west bounds.

    MM.MapExtent = function(north, west, south, east) {
        if (arguments[0] instanceof MM.Location) {
            var northwest = arguments[0];
            north = northwest.lat;
            west = northwest.lon;
        }
        if (arguments[1] instanceof MM.Location) {
            var southeast = arguments[1];
            south = southeast.lat;
            east = southeast.lon;
        }
        if (isNaN(south)) south = north;
        if (isNaN(east)) east = west;
        this.north = Math.max(north, south);
        this.south = Math.min(north, south);
        this.east = Math.max(east, west);
        this.west = Math.min(east, west);
    };

    MM.MapExtent.prototype = {
        // boundary attributes
        north: 0,
        south: 0,
        east: 0,
        west: 0,

        copy: function() {
            return new MM.MapExtent(this.north, this.west, this.south, this.east);
        },

        toString: function(precision) {
            if (isNaN(precision)) precision = 3;
            return [
                this.north.toFixed(precision),
                this.west.toFixed(precision),
                this.south.toFixed(precision),
                this.east.toFixed(precision)
            ].join(", ");
        },

        // getters for the corner locations
        northWest: function() {
            return new MM.Location(this.north, this.west);
        },
        southEast: function() {
            return new MM.Location(this.south, this.east);
        },
        northEast: function() {
            return new MM.Location(this.north, this.east);
        },
        southWest: function() {
            return new MM.Location(this.south, this.west);
        },
        // getter for the center location
        center: function() {
            return new MM.Location(
                this.south + (this.north - this.south) / 2,
                this.east + (this.west - this.east) / 2
            );
        },

        // extend the bounds to include a location's latitude and longitude
        encloseLocation: function(loc) {
            if (loc.lat > this.north) this.north = loc.lat;
            if (loc.lat < this.south) this.south = loc.lat;
            if (loc.lon > this.east) this.east = loc.lon;
            if (loc.lon < this.west) this.west = loc.lon;
        },

        // extend the bounds to include multiple locations
        encloseLocations: function(locations) {
            var len = locations.length;
            for (var i = 0; i < len; i++) {
                this.encloseLocation(locations[i]);
            }
        },

        // reset bounds from a list of locations
        setFromLocations: function(locations) {
            var len = locations.length,
                first = locations[0];
            this.north = this.south = first.lat;
            this.east = this.west = first.lon;
            for (var i = 1; i < len; i++) {
                this.encloseLocation(locations[i]);
            }
        },

        // extend the bounds to include another extent
        encloseExtent: function(extent) {
            if (extent.north > this.north) this.north = extent.north;
            if (extent.south < this.south) this.south = extent.south;
            if (extent.east > this.east) this.east = extent.east;
            if (extent.west < this.west) this.west = extent.west;
        },

        // determine if a location is within this extent
        containsLocation: function(loc) {
            return loc.lat >= this.south
                && loc.lat <= this.north
                && loc.lon >= this.west
                && loc.lon <= this.east;
        },

        // turn an extent into an array of locations containing its northwest
        // and southeast corners (used in MM.Map.setExtent())
        toArray: function() {
            return [this.northWest(), this.southEast()];
        }
    };

    MM.MapExtent.fromString = function(str) {
        var parts = str.split(/\s*,\s*/);
        if (parts.length != 4) {
            throw "Invalid extent string (expecting 4 comma-separated numbers)";
        }
        return new MM.MapExtent(
            parseFloat(parts[0]),
            parseFloat(parts[1]),
            parseFloat(parts[2]),
            parseFloat(parts[3])
        );
    };

    MM.MapExtent.fromArray = function(locations) {
        var extent = new MM.MapExtent();
        extent.setFromLocations(locations);
        return extent;
    };

    // Transformation
    // --------------
    MM.Transformation = function(ax, bx, cx, ay, by, cy) {
        this.ax = ax;
        this.bx = bx;
        this.cx = cx;
        this.ay = ay;
        this.by = by;
        this.cy = cy;
    };

    MM.Transformation.prototype = {

        ax: 0,
        bx: 0,
        cx: 0,
        ay: 0,
        by: 0,
        cy: 0,

        transform: function(point) {
            return new MM.Point(this.ax * point.x + this.bx * point.y + this.cx,
                                this.ay * point.x + this.by * point.y + this.cy);
        },

        untransform: function(point) {
            return new MM.Point((point.x * this.by - point.y * this.bx -
                               this.cx * this.by + this.cy * this.bx) /
                              (this.ax * this.by - this.ay * this.bx),
                              (point.x * this.ay - point.y * this.ax -
                               this.cx * this.ay + this.cy * this.ax) /
                              (this.bx * this.ay - this.by * this.ax));
        }

    };


    // Generates a transform based on three pairs of points,
    // a1 -> a2, b1 -> b2, c1 -> c2.
    MM.deriveTransformation = function(a1x, a1y, a2x, a2y,
                                       b1x, b1y, b2x, b2y,
                                       c1x, c1y, c2x, c2y) {
        var x = MM.linearSolution(a1x, a1y, a2x,
                                  b1x, b1y, b2x,
                                  c1x, c1y, c2x);
        var y = MM.linearSolution(a1x, a1y, a2y,
                                  b1x, b1y, b2y,
                                  c1x, c1y, c2y);
        return new MM.Transformation(x[0], x[1], x[2], y[0], y[1], y[2]);
    };

    // Solves a system of linear equations.
    //
    //     t1 = (a * r1) + (b + s1) + c
    //     t2 = (a * r2) + (b + s2) + c
    //     t3 = (a * r3) + (b + s3) + c
    //
    // r1 - t3 are the known values.
    // a, b, c are the unknowns to be solved.
    // returns the a, b, c coefficients.
    MM.linearSolution = function(r1, s1, t1, r2, s2, t2, r3, s3, t3) {
        // make them all floats
        r1 = parseFloat(r1);
        s1 = parseFloat(s1);
        t1 = parseFloat(t1);
        r2 = parseFloat(r2);
        s2 = parseFloat(s2);
        t2 = parseFloat(t2);
        r3 = parseFloat(r3);
        s3 = parseFloat(s3);
        t3 = parseFloat(t3);

        var a = (((t2 - t3) * (s1 - s2)) - ((t1 - t2) * (s2 - s3))) /
              (((r2 - r3) * (s1 - s2)) - ((r1 - r2) * (s2 - s3)));

        var b = (((t2 - t3) * (r1 - r2)) - ((t1 - t2) * (r2 - r3))) /
              (((s2 - s3) * (r1 - r2)) - ((s1 - s2) * (r2 - r3)));

        var c = t1 - (r1 * a) - (s1 * b);
        return [ a, b, c ];
    };
    // Projection
    // ----------

    // An abstract class / interface for projections
    MM.Projection = function(zoom, transformation) {
        if (!transformation) {
            transformation = new MM.Transformation(1, 0, 0, 0, 1, 0);
        }
        this.zoom = zoom;
        this.transformation = transformation;
    };

    MM.Projection.prototype = {

        zoom: 0,
        transformation: null,

        rawProject: function(point) {
            throw "Abstract method not implemented by subclass.";
        },

        rawUnproject: function(point) {
            throw "Abstract method not implemented by subclass.";
        },

        project: function(point) {
            point = this.rawProject(point);
            if(this.transformation) {
                point = this.transformation.transform(point);
            }
            return point;
        },

        unproject: function(point) {
            if(this.transformation) {
                point = this.transformation.untransform(point);
            }
            point = this.rawUnproject(point);
            return point;
        },

        locationCoordinate: function(location) {
            var point = new MM.Point(Math.PI * location.lon / 180.0,
                                     Math.PI * location.lat / 180.0);
            point = this.project(point);
            return new MM.Coordinate(point.y, point.x, this.zoom);
        },

        coordinateLocation: function(coordinate) {
            coordinate = coordinate.zoomTo(this.zoom);
            var point = new MM.Point(coordinate.column, coordinate.row);
            point = this.unproject(point);
            return new MM.Location(180.0 * point.y / Math.PI,
                                   180.0 * point.x / Math.PI);
        }
    };

    // A projection for equilateral maps, based on longitude and latitude
    MM.LinearProjection = function(zoom, transformation) {
        MM.Projection.call(this, zoom, transformation);
    };

    // The Linear projection doesn't reproject points
    MM.LinearProjection.prototype = {
        rawProject: function(point) {
            return new MM.Point(point.x, point.y);
        },
        rawUnproject: function(point) {
            return new MM.Point(point.x, point.y);
        }
    };

    MM.extend(MM.LinearProjection, MM.Projection);

    MM.MercatorProjection = function(zoom, transformation) {
        // super!
        MM.Projection.call(this, zoom, transformation);
    };

    // Project lon/lat points into meters required for Mercator
    MM.MercatorProjection.prototype = {
        rawProject: function(point) {
            return new MM.Point(point.x,
                         Math.log(Math.tan(0.25 * Math.PI + 0.5 * point.y)));
        },

        rawUnproject: function(point) {
            return new MM.Point(point.x,
                    2 * Math.atan(Math.pow(Math.E, point.y)) - 0.5 * Math.PI);
        }
    };

    MM.extend(MM.MercatorProjection, MM.Projection);

    // Providers
    // ---------
    // Providers provide tile URLs and possibly elements for layers.
    MM.MapProvider = function(getTileUrl) {
        if (getTileUrl) {
            this.getTileUrl = getTileUrl;
        }
    };

    MM.MapProvider.prototype = {

        // these are limits for available *tiles*
        // panning limits will be different (since you can wrap around columns)
        // but if you put Infinity in here it will screw up sourceCoordinate
        tileLimits: [ new MM.Coordinate(0,0,0),             // top left outer
                      new MM.Coordinate(1,1,0).zoomTo(18) ], // bottom right inner

        getTileUrl: function(coordinate) {
            throw "Abstract method not implemented by subclass.";
        },

        getTile: function(coordinate) {
            throw "Abstract method not implemented by subclass.";
        },

        releaseTile: function(element) {
            throw "Abstract method not implemented by subclass.";
        },

        // use this to tell MapProvider that tiles only exist between certain zoom levels.
        // should be set separately on Map to restrict interactive zoom/pan ranges
        setZoomRange: function(minZoom, maxZoom) {
            this.tileLimits[0] = this.tileLimits[0].zoomTo(minZoom);
            this.tileLimits[1] = this.tileLimits[1].zoomTo(maxZoom);
        },

        // return null if coord is above/below row extents
        // wrap column around the world if it's outside column extents
        // ... you should override this function if you change the tile limits
        // ... see enforce-limits in examples for details
        sourceCoordinate: function(coord) {
            var TL = this.tileLimits[0].zoomTo(coord.zoom);
            var BR = this.tileLimits[1].zoomTo(coord.zoom);
            var vSize = BR.row - TL.row;
            if (coord.row < 0 | coord.row >= vSize) {
                // it's too high or too low:
                return null;
            }
            var hSize = BR.column - TL.column;
            // assume infinite horizontal scrolling
            var wrappedColumn = coord.column % hSize;
            while (wrappedColumn < 0) {
                wrappedColumn += hSize;
            }
            return new MM.Coordinate(coord.row, wrappedColumn, coord.zoom);
        }
    };

    /**
     * FIXME: need a better explanation here! This is a pretty crucial part of
     * understanding how to use ModestMaps.
     *
     * TemplatedMapProvider is a tile provider that generates tile URLs from a
     * template string by replacing the following bits for each tile
     * coordinate:
     *
     * {Z}: the tile's zoom level (from 1 to ~20)
     * {X}: the tile's X, or column (from 0 to a very large number at higher
     * zooms)
     * {Y}: the tile's Y, or row (from 0 to a very large number at higher
     * zooms)
     *
     * E.g.:
     *
     * var osm = new MM.TemplatedMapProvider("http://tile.openstreetmap.org/{Z}/{X}/{Y}.png");
     *
     * Or:
     *
     * var placeholder = new MM.TemplatedMapProvider("http://placehold.it/256/f0f/fff.png&text={Z}/{X}/{Y}");
     *
     */
    MM.TemplatedMapProvider = function(template, subdomains)
    {
        var isQuadKey = false;
        if (template.match(/{(Q|quadkey)}/)) {
            isQuadKey = true;
            // replace Microsoft style substitution strings
            template = template
                .replace('{subdomains}', '{S}')
                .replace('{zoom}', '{Z}')
                .replace('{quadkey}', '{Q}');
        }

        var hasSubdomains = false;
        if (subdomains && subdomains.length && template.indexOf("{S}") >= 0) {
            hasSubdomains = true;
        }

        var getTileUrl = function(coordinate) {
            var coord = this.sourceCoordinate(coordinate);
            if (!coord) {
                return null;
            }
            var base = template;
            if (hasSubdomains) {
                var index = parseInt(coord.zoom + coord.row + coord.column, 10) % subdomains.length;
                base = base.replace('{S}', subdomains[index]);
            }
            if (isQuadKey) {
                return base
                    .replace('{Z}', coord.zoom.toFixed(0))
                    .replace('{Q}', this.quadKey(coord.row, coord.column, coord.zoom));
            } else {
                return base
                    .replace('{Z}', coord.zoom.toFixed(0))
                    .replace('{X}', coord.column.toFixed(0))
                    .replace('{Y}', coord.row.toFixed(0));
            }
        };
    
        MM.MapProvider.call(this, getTileUrl);
    };

    MM.TemplatedMapProvider.prototype = {
        // quadKey generator
        quadKey: function(row, column, zoom) {
            var key = "";
            for (var i = 1; i <= zoom; i++) {
                key += (((row >> zoom - i) & 1) << 1) | ((column >> zoom - i) & 1);
            }
            return key || "0";
        }
    };

    MM.extend(MM.TemplatedMapProvider, MM.MapProvider);

   /**
    * Possible new kind of provider that deals in elements.
    */
    MM.TilePaintingProvider = function(template_provider) {
        this.template_provider = template_provider;
    };

    MM.TilePaintingProvider.prototype = {

        getTile: function(coord) {
            return this.template_provider.getTileUrl(coord);
        },

        releaseTile: function(coord) {
        }
    };

    MM.extend(MM.TilePaintingProvider, MM.MapProvider);
    // Event Handlers
    // --------------

    // A utility function for finding the offset of the
    // mouse from the top-left of the page
    MM.getMousePoint = function(e, map) {
        // start with just the mouse (x, y)
        var point = new MM.Point(e.clientX, e.clientY);

        // correct for scrolled document
        point.x += document.body.scrollLeft + document.documentElement.scrollLeft;
        point.y += document.body.scrollTop + document.documentElement.scrollTop;

        // correct for nested offsets in DOM
        for (var node = map.parent; node; node = node.offsetParent) {
            point.x -= node.offsetLeft;
            point.y -= node.offsetTop;
        }
        return point;
    };

    // A handler that allows mouse-wheel zooming - zooming in
    // when page would scroll up, and out when the page would scroll down.
    MM.MouseWheelHandler = function(map, precise) {
        // only init() if we get a map
        if (map) {
            this.init(map, precise);
        // allow (null, true) as constructor args
        } else if (arguments.length > 1) {
            this.precise = precise ? true : false;
        }
    };

    MM.MouseWheelHandler.prototype = {
        precise: false,
        // MM.Point to zoom about
        centerPoint: null,
        // MM.Location to zoom about
        centerLocation: null,

        init: function(map) {
            this.map = map;
            this._mouseWheel = MM.bind(this.mouseWheel, this);

            this._zoomDiv = document.body.appendChild(document.createElement('div'));
            this._zoomDiv.style.cssText = 'visibility:hidden;top:0;height:0;width:0;overflow-y:scroll';
            var innerDiv = this._zoomDiv.appendChild(document.createElement('div'));
            innerDiv.style.height = '2000px';
            MM.addEvent(map.parent, 'mousewheel', this._mouseWheel);
        },

        remove: function() {
            MM.removeEvent(this.map.parent, 'mousewheel', this._mouseWheel);
            this._zoomDiv.parentNode.removeChild(this._zoomDiv);
        },

        mouseWheel: function(e) {
            var delta = 0;
            this.prevTime = this.prevTime || new Date().getTime();

            try {
                this._zoomDiv.scrollTop = 1000;
                this._zoomDiv.dispatchEvent(e);
                delta = 1000 - this._zoomDiv.scrollTop;
            } catch (error) {
                delta = e.wheelDelta || (-e.detail * 5);
            }

            // limit mousewheeling to once every 200ms
            var timeSince = new Date().getTime() - this.prevTime;

            if (Math.abs(delta) > 0 && (timeSince > 200) && !this.precise) {
                var point = this.getZoomPoint(e);
                this.map.zoomByAbout(delta > 0 ? 1 : -1, point);

                this.prevTime = new Date().getTime();
            } else if (this.precise) {
                var point = this.getZoomPoint(e);
                this.map.zoomByAbout(delta * 0.001, point);
            }

            // Cancel the event so that the page doesn't scroll
            return MM.cancelEvent(e);
        },

        getZoomPoint: function(e) {
            if (this.centerPoint) {
                return this.centerPoint;
            } else if (this.centerLocation) {
                return this.map.locationPoint(this.centerLocation);
            }
            return MM.getMousePoint(e, this.map);
        }
    };

    // Handle double clicks, that zoom the map in one zoom level.
    MM.DoubleClickHandler = function(map) {
        if (map !== undefined) {
            this.init(map);
        }
    };

    MM.DoubleClickHandler.prototype = {

        init: function(map) {
            this.map = map;
            this._doubleClick = MM.bind(this.doubleClick, this);
            MM.addEvent(map.parent, 'dblclick', this._doubleClick);
        },

        remove: function() {
            MM.removeEvent(this.map.parent, 'dblclick', this._doubleClick);
        },

        doubleClick: function(e) {
            // Ensure that this handler is attached once.
            // Get the point on the map that was double-clicked
            var point = MM.getMousePoint(e, this.map);

            // use shift-double-click to zoom out
            this.map.zoomByAbout(e.shiftKey ? -1 : 1, point);

            return MM.cancelEvent(e);
        }
    };

    // Handle the use of mouse dragging to pan the map.
    MM.DragHandler = function(map) {
        if (map !== undefined) {
            this.init(map);
        }
    };

    MM.DragHandler.prototype = {

        init: function(map) {
            this.map = map;
            this._mouseDown = MM.bind(this.mouseDown, this);
            MM.addEvent(map.parent, 'mousedown', this._mouseDown);
        },

        remove: function() {
            MM.removeEvent(this.map.parent, 'mousedown', this._mouseDown);
        },

        mouseDown: function(e) {
            MM.addEvent(document, 'mouseup', this._mouseUp = MM.bind(this.mouseUp, this));
            MM.addEvent(document, 'mousemove', this._mouseMove = MM.bind(this.mouseMove, this));

            this.prevMouse = new MM.Point(e.clientX, e.clientY);
            this.map.parent.style.cursor = 'move';

            return MM.cancelEvent(e);
        },

        mouseMove: function(e) {
            if (this.prevMouse) {
                this.map.panBy(
                    e.clientX - this.prevMouse.x,
                    e.clientY - this.prevMouse.y);
                this.prevMouse.x = e.clientX;
                this.prevMouse.y = e.clientY;
                this.prevMouse.t = +new Date();
            }

            return MM.cancelEvent(e);
        },

        mouseUp: function(e) {
            MM.removeEvent(document, 'mouseup', this._mouseUp);
            MM.removeEvent(document, 'mousemove', this._mouseMove);

            this.prevMouse = null;
            this.map.parent.style.cursor = '';

            return MM.cancelEvent(e);
        }
    };

    // A shortcut for adding drag, double click,
    // and mouse wheel events to the map. This is the default
    // handler attached to a map if the handlers argument isn't given.
    MM.MouseHandler = function(map) {
        if (map !== undefined) {
            this.init(map);
        }
    };

    MM.MouseHandler.prototype = {
        init: function(map) {
            this.map = map;
            this.handlers = [
                new MM.DragHandler(map),
                new MM.DoubleClickHandler(map),
                new MM.MouseWheelHandler(map)
            ];
        },
        remove: function() {
            for (var i = 0; i < this.handlers.length; i++) {
                this.handlers[i].remove();
            }
        }
    };

    var HAS_HASHCHANGE = (function() {
        var doc_mode = window.documentMode;
        return ('onhashchange' in window)
            && (doc_mode === undefined || doc_mode > 7);
    })();

    MM.Hash = function(map) {
        this.onMapMove = MM.bind(this.onMapMove, this);
        this.onHashChange = MM.bind(this.onHashChange, this);
        if (map) {
            this.init(map);
        }
    };

    MM.Hash.prototype = {
        map: null,
        lastHash: null,

        parseHash: function(hash) {
            var args = hash.split("/");
            if (args.length == 3) {
                var zoom = parseInt(args[0]),
                    lat = parseFloat(args[1]),
                    lon = parseFloat(args[2]);
                if (isNaN(zoom) || isNaN(lat) || isNaN(lon)) {
                    return false;
                } else {
                    return {
                        center: new MM.Location(lat, lon),
                        zoom: zoom
                    };
                }
            } else {
                return false;
            }
        },

        formatHash: function(map) {
            var center = map.getCenter(),
                zoom = map.getZoom(),
                precision = Math.max(0, Math.ceil(Math.log(zoom) / Math.LN2));
            return "#" + [zoom,
                center.lat.toFixed(precision),
                center.lon.toFixed(precision)
            ].join("/");
        },

        init: function(map) {
            this.map = map;
            this.map.addCallback("drawn", this.onMapMove);
            // reset the hash
            this.lastHash = null;
            this.onHashChange();

            if (!this.isListening) {
                this.startListening();
            }
        },

        remove: function() {
            this.map = null;
            if (this.isListening) {
                this.stopListening();
            }
        },

        onMapMove: function(map) {
            // bail if we're moving the map (updating from a hash),
            // or if the map has no zoom set
            if (this.movingMap || this.map.zoom === 0) {
                return false;
            }
            var hash = this.formatHash(map);
            if (this.lastHash != hash) {
                location.replace(hash);
                this.lastHash = hash;
            }
        },

        movingMap: false,
        update: function() {
            var hash = location.hash;
            if (hash === this.lastHash) {
                // console.info("(no change)");
                return;
            }
            var sansHash = hash.substr(1),
                parsed = this.parseHash(sansHash);
            if (parsed) {
                // console.log("parsed:", parsed.zoom, parsed.center.toString());
                this.movingMap = true;
                this.map.setCenterZoom(parsed.center, parsed.zoom);
                this.movingMap = false;
            } else {
                // console.warn("parse error; resetting:", this.map.getCenter(), this.map.getZoom());
                this.onMapMove(this.map);
            }
        },

        // defer hash change updates every 100ms
        changeDefer: 100,
        changeTimeout: null,
        onHashChange: function() {
            // throttle calls to update() so that they only happen every
            // `changeDefer` ms
            if (!this.changeTimeout) {
                var that = this;
                this.changeTimeout = setTimeout(function() {
                    that.update();
                    that.changeTimeout = null;
                }, this.changeDefer);
            }
        },

        isListening: false,
        hashChangeInterval: null,
        startListening: function() {
            if (HAS_HASHCHANGE) {
                window.addEventListener("hashchange", this.onHashChange, false);
            } else {
                clearInterval(this.hashChangeInterval);
                this.hashChangeInterval = setInterval(this.onHashChange, 50);
            }
            this.isListening = true;
        },

        stopListening: function() {
            if (HAS_HASHCHANGE) {
                window.removeEventListener("hashchange", this.onHashChange);
            } else {
                clearInterval(this.hashChangeInterval);
            }
            this.isListening = false;
        }
    };
    MM.TouchHandler = function(map, options) {
        if (map) {
            this.init(map, options);
        }
    };

    MM.TouchHandler.prototype = {

        maxTapTime: 250,
        maxTapDistance: 30,
        maxDoubleTapDelay: 350,
        locations: {},
        taps: [],
        wasPinching: false,
        lastPinchCenter: null,

        init: function(map, options) {
            this.map = map;
            options = options || {};

            // Fail early if this isn't a touch device.
            if (!this.isTouchable()) return false;

            this._touchStartMachine = MM.bind(this.touchStartMachine, this);
            this._touchMoveMachine = MM.bind(this.touchMoveMachine, this);
            this._touchEndMachine = MM.bind(this.touchEndMachine, this);
            MM.addEvent(map.parent, 'touchstart',
                this._touchStartMachine);
            MM.addEvent(map.parent, 'touchmove',
                this._touchMoveMachine);
            MM.addEvent(map.parent, 'touchend',
                this._touchEndMachine);

            this.options = {};
            this.options.snapToZoom = options.snapToZoom || true;
        },

        isTouchable: function() {
             var el = document.createElement('div');
             el.setAttribute('ongesturestart', 'return;');
             return (typeof el.ongesturestart === 'function');
        },

        remove: function() {
            // Fail early if this isn't a touch device.
            if (!this.isTouchable()) return false;

            MM.removeEvent(this.map.parent, 'touchstart',
                this._touchStartMachine);
            MM.removeEvent(this.map.parent, 'touchmove',
                this._touchMoveMachine);
            MM.removeEvent(this.map.parent, 'touchend',
                this._touchEndMachine);
        },

        updateTouches: function(e) {
            for (var i = 0; i < e.touches.length; i += 1) {
                var t = e.touches[i];
                if (t.identifier in this.locations) {
                    var l = this.locations[t.identifier];
                    l.x = t.screenX;
                    l.y = t.screenY;
                    l.scale = e.scale;
                }
                else {
                    this.locations[t.identifier] = {
                        scale: e.scale,
                        startPos: { x: t.screenX, y: t.screenY },
                        x: t.screenX,
                        y: t.screenY,
                        time: new Date().getTime()
                    };
                }
            }
        },

        // Test whether touches are from the same source -
        // whether this is the same touchmove event.
        sameTouch: function(event, touch) {
            return (event && event.touch) &&
                (touch.identifier == event.touch.identifier);
        },

        touchStartMachine: function(e) {
            this.updateTouches(e);
            return MM.cancelEvent(e);
        },

        touchMoveMachine: function(e) {
            switch (e.touches.length) {
                case 1:
                    this.onPanning(e.touches[0]);
                    break;
                case 2:
                    this.onPinching(e);
                    break;
            }
            this.updateTouches(e);
            return MM.cancelEvent(e);
        },

        touchEndMachine: function(e) {
            var now = new Date().getTime();
            // round zoom if we're done pinching
            if (e.touches.length === 0 && this.wasPinching) {
                this.onPinched(this.lastPinchCenter);
            }

            // Look at each changed touch in turn.
            for (var i = 0; i < e.changedTouches.length; i += 1) {
                var t = e.changedTouches[i],
                    loc = this.locations[t.identifier];
                // if we didn't see this one (bug?)
                // or if it was consumed by pinching already
                // just skip to the next one
                if (!loc || loc.wasPinch) {
                    continue;
                }

                // we now know we have an event object and a
                // matching touch that's just ended. Let's see
                // what kind of event it is based on how long it
                // lasted and how far it moved.
                var pos = { x: t.screenX, y: t.screenY },
                    time = now - loc.time,
                    travel = MM.Point.distance(pos, loc.startPos);
                if (travel > this.maxTapDistance) {
                    // we will to assume that the drag has been handled separately
                } else if (time > this.maxTapTime) {
                    // close in space, but not in time: a hold
                    pos.end = now;
                    pos.duration = time;
                    this.onHold(pos);
                } else {
                    // close in both time and space: a tap
                    pos.time = now;
                    this.onTap(pos);
                }
            }

            // Weird, sometimes an end event doesn't get thrown
            // for a touch that nevertheless has disappeared.
            // Still, this will eventually catch those ids:

            var validTouchIds = {};
            for (var j = 0; j < e.touches.length; j++) {
                validTouchIds[e.touches[j].identifier] = true;
            }
            for (var id in this.locations) {
                if (!(id in validTouchIds)) {
                    delete validTouchIds[id];
                }
            }

            return MM.cancelEvent(e);
        },

        onHold: function(hold) {
            // TODO
        },

        // Handle a tap event - mainly watch for a doubleTap
        onTap: function(tap) {
            if (this.taps.length &&
                (tap.time - this.taps[0].time) < this.maxDoubleTapDelay) {
                this.onDoubleTap(tap);
                this.taps = [];
                return;
            }
            this.taps = [tap];
        },

        // Handle a double tap by zooming in a single zoom level to a
        // round zoom.
        onDoubleTap: function(tap) {

            var z = this.map.getZoom(), // current zoom
                tz = Math.round(z) + 1, // target zoom
                dz = tz - z;            // desired delate
            // zoom in to a round number
            var p = new MM.Point(tap.x, tap.y);
            this.map.zoomByAbout(dz, p);
        },

        // Re-transform the actual map parent's CSS transformation
        onPanning: function(touch) {
            var pos = { x: touch.screenX, y: touch.screenY },
                prev = this.locations[touch.identifier];
            this.map.panBy(pos.x - prev.x, pos.y - prev.y);
        },

        onPinching: function(e) {
            // use the first two touches and their previous positions
            var t0 = e.touches[0],
                t1 = e.touches[1],
                p0 = new MM.Point(t0.screenX, t0.screenY),
                p1 = new MM.Point(t1.screenX, t1.screenY),
                l0 = this.locations[t0.identifier],
                l1 = this.locations[t1.identifier];

            // mark these touches so they aren't used as taps/holds
            l0.wasPinch = true;
            l1.wasPinch = true;

            // scale about the center of these touches
            var center = MM.Point.interpolate(p0, p1, 0.5);

            this.map.zoomByAbout(
                Math.log(e.scale) / Math.LN2 -
                Math.log(l0.scale) / Math.LN2,
                center );

            // pan from the previous center of these touches
            var prevCenter = MM.Point.interpolate(l0, l1, 0.5);

            this.map.panBy(center.x - prevCenter.x,
                           center.y - prevCenter.y);
            this.wasPinching = true;
            this.lastPinchCenter = center;
        },

        // When a pinch event ends, round the zoom of the map.
        onPinched: function(p) {
            // TODO: easing
            if (this.options.snapToZoom) {
                var z = this.map.getZoom(), // current zoom
                    tz = Math.round(z);     // target zoom
                this.map.zoomByAbout(tz - z, p);
            }
            this.wasPinching = false;
        }
    };
    // CallbackManager
    // ---------------
    // A general-purpose event binding manager used by `Map`
    // and `RequestManager`

    // Construct a new CallbackManager, with an list of
    // supported events.
    MM.CallbackManager = function(owner, events) {
        this.owner = owner;
        this.callbacks = {};
        for (var i = 0; i < events.length; i++) {
            this.callbacks[events[i]] = [];
        }
    };

    // CallbackManager does simple event management for modestmaps
    MM.CallbackManager.prototype = {
        // The element on which callbacks will be triggered.
        owner: null,

        // An object of callbacks in the form
        //
        //     { event: function }
        callbacks: null,

        // Add a callback to this object - where the `event` is a string of
        // the event name and `callback` is a function.
        addCallback: function(event, callback) {
            if (typeof(callback) == 'function' && this.callbacks[event]) {
                this.callbacks[event].push(callback);
            }
        },

        // Remove a callback. The given function needs to be equal (`===`) to
        // the callback added in `addCallback`, so named functions should be
        // used as callbacks.
        removeCallback: function(event, callback) {
            if (typeof(callback) == 'function' && this.callbacks[event]) {
                var cbs = this.callbacks[event],
                    len = cbs.length;
                for (var i = 0; i < len; i++) {
                  if (cbs[i] === callback) {
                    cbs.splice(i,1);
                    break;
                  }
                }
            }
        },

        // Trigger a callback, passing it an object or string from the second
        // argument.
        dispatchCallback: function(event, message) {
            if(this.callbacks[event]) {
                for (var i = 0; i < this.callbacks[event].length; i += 1) {
                    try {
                        this.callbacks[event][i](this.owner, message);
                    } catch(e) {
                        //console.log(e);
                        // meh
                    }
                }
            }
        }
    };
    // RequestManager
    // --------------
    // an image loading queue
    MM.RequestManager = function() {

        // The loading bay is a document fragment to optimize appending, since
        // the elements within are invisible. See
        //  [this blog post](http://ejohn.org/blog/dom-documentfragments/).
        this.loadingBay = document.createDocumentFragment();

        this.requestsById = {};
        this.openRequestCount = 0;

        this.maxOpenRequests = 4;
        this.requestQueue = [];

        this.callbackManager = new MM.CallbackManager(this, ['requestcomplete']);
    };

    MM.RequestManager.prototype = {

        // DOM element, hidden, for making sure images dispatch complete events
        loadingBay: null,

        // all known requests, by ID
        requestsById: null,

        // current pending requests
        requestQueue: null,

        // current open requests (children of loadingBay)
        openRequestCount: null,

        // the number of open requests permitted at one time, clamped down
        // because of domain-connection limits.
        maxOpenRequests: null,

        // for dispatching 'requestcomplete'
        callbackManager: null,

        addCallback: function(event, callback) {
            this.callbackManager.addCallback(event,callback);
        },

        removeCallback: function(event, callback) {
            this.callbackManager.removeCallback(event,callback);
        },

        dispatchCallback: function(event, message) {
            this.callbackManager.dispatchCallback(event,message);
        },

        // Clear everything in the queue by excluding nothing
        clear: function() {
            this.clearExcept({});
        },
        
        clearRequest: function(id) {
            if(id in this.requestsById) {
                delete this.requestsById[id];
            }
            
            for(var i = 0; i < this.requestQueue.length; i++) {
                var request = this.requestQueue[i];
                if(request && request.id == id) {
                    this.requestQueue[i] = null;
                }
            }
        },
        
        // Clear everything in the queue except for certain keys, specified
        // by an object of the form
        //
        //     { key: throwawayvalue }
        clearExcept: function(validIds) {

            // clear things from the queue first...
            for (var i = 0; i < this.requestQueue.length; i++) {
                var request = this.requestQueue[i];
                if (request && !(request.id in validIds)) {
                    this.requestQueue[i] = null;
                }
            }

            // then check the loadingBay...
            var openRequests = this.loadingBay.childNodes;
            for (var j = openRequests.length-1; j >= 0; j--) {
                var img = openRequests[j];
                if (!(img.id in validIds)) {
                    this.loadingBay.removeChild(img);
                    this.openRequestCount--;
                    /* console.log(this.openRequestCount + " open requests"); */
                    img.src = img.coord = img.onload = img.onerror = null;
                }
            }
            
            // hasOwnProperty protects against prototype additions
            // > "The standard describes an augmentable Object.prototype.
            //  Ignore standards at your own peril."
            // -- http://www.yuiblog.com/blog/2006/09/26/for-in-intrigue/
            for (var id in this.requestsById) {
                if (this.requestsById.hasOwnProperty(id)) {
                    if (!(id in validIds)) {
                        var requestToRemove = this.requestsById[id];
                        // whether we've done the request or not...
                        delete this.requestsById[id];
                        if (requestToRemove !== null) {
                            requestToRemove =
                                requestToRemove.id =
                                requestToRemove.coord =
                                requestToRemove.url = null;
                        }
                    }
                }
            }
        },

        // Given a tile id, check whether the RequestManager is currently
        // requesting it and waiting for the result.
        hasRequest: function(id) {
            return (id in this.requestsById);
        },

        // * TODO: remove dependency on coord (it's for sorting, maybe call it data?)
        // * TODO: rename to requestImage once it's not tile specific
        requestTile: function(id, coord, url) {
            if (!(id in this.requestsById)) {
                var request = { id: id, coord: coord.copy(), url: url };
                // if there's no url just make sure we don't request this image again
                this.requestsById[id] = request;
                if (url) {
                    this.requestQueue.push(request);
                    /* console.log(this.requestQueue.length + ' pending requests'); */
                }
            }
        },
        
        getProcessQueue: function() {
            // let's only create this closure once...
            if (!this._processQueue) {
                var theManager = this;
                this._processQueue = function() {
                    theManager.processQueue();
                };
            }
            return this._processQueue;
        },
        
        // Select images from the `requestQueue` and create image elements for
        // them, attaching their load events to the function returned by
        // `this.getLoadComplete()` so that they can be added to the map.
        processQueue: function(sortFunc) {
            // When the request queue fills up beyond 8, start sorting the
            // requests so that spiral-loading or another pattern can be used.
            if (sortFunc && this.requestQueue.length > 8) {
                this.requestQueue.sort(sortFunc);
            }
            while (this.openRequestCount < this.maxOpenRequests && this.requestQueue.length > 0) {
                var request = this.requestQueue.pop();
                if (request) {
                    this.openRequestCount++;
                    /* console.log(this.openRequestCount + ' open requests'); */

                    // JSLitmus benchmark shows createElement is a little faster than
                    // new Image() in Firefox and roughly the same in Safari:
                    // http://tinyurl.com/y9wz2jj http://tinyurl.com/yes6rrt
                    var img = document.createElement('img');

                    // FIXME: id is technically not unique in document if there
                    // are two Maps but toKey is supposed to be fast so we're trying
                    // to avoid a prefix ... hence we can't use any calls to
                    // `document.getElementById()` to retrieve images
                    img.id = request.id;
                    img.style.position = 'absolute';
                    // * FIXME: store this elsewhere to avoid scary memory leaks?
                    // * FIXME: call this 'data' not 'coord' so that RequestManager is less Tile-centric?
                    img.coord = request.coord;
                    // add it to the DOM in a hidden layer, this is a bit of a hack, but it's
                    // so that the event we get in image.onload has srcElement assigned in IE6
                    this.loadingBay.appendChild(img);
                    // set these before img.src to avoid missing an img that's already cached
                    img.onload = img.onerror = this.getLoadComplete();
                    img.src = request.url;

                    // keep things tidy
                    request = request.id = request.coord = request.url = null;
                }
            }
        },

        _loadComplete: null,

        // Get the singleton `_loadComplete` function that is called on image
        // load events, either removing them from the queue and dispatching an
        // event to add them to the map, or deleting them if the image failed
        // to load.
        getLoadComplete: function() {
            // let's only create this closure once...
            if (!this._loadComplete) {
                var theManager = this;
                this._loadComplete = function(e) {
                    // this is needed because we don't use MM.addEvent for images
                    e = e || window.event;

                    // srcElement for IE, target for FF, Safari etc.
                    var img = e.srcElement || e.target;

                    // unset these straight away so we don't call this twice
                    img.onload = img.onerror = null;
                    
                    // pull it back out of the (hidden) DOM
                    // so that draw will add it correctly later
                    theManager.loadingBay.removeChild(img);
                    theManager.openRequestCount--;
                    delete theManager.requestsById[img.id];

                    /* console.log(theManager.openRequestCount + ' open requests'); */

                    // NB:- complete is also true onerror if we got a 404
                    if (e.type === 'load' && (img.complete ||
                        (img.readyState && img.readyState == 'complete'))) {
                        theManager.dispatchCallback('requestcomplete', img);
                    } else {
                        // if it didn't finish clear its src to make sure it
                        // really stops loading
                        // FIXME: we'll never retry because this id is still
                        // in requestsById - is that right?
                        img.src = null;
                    }

                    // keep going in the same order
                    // use `setTimeout()` to avoid the IE recursion limit, see
                    // http://cappuccino.org/discuss/2010/03/01/internet-explorer-global-variables-and-stack-overflows/
                    // and https://github.com/stamen/modestmaps-js/issues/12
                    setTimeout(theManager.getProcessQueue(), 0);

                };
            }
            return this._loadComplete;
        }

    };

    // Layer

    MM.Layer = function(provider, parent) {
        this.parent = parent || document.createElement('div');
        this.parent.style.cssText = 'position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; margin: 0; padding: 0; z-index: 0';

        this.levels = {};

        this.requestManager = new MM.RequestManager();
        this.requestManager.addCallback('requestcomplete', this.getTileComplete());

        if (provider) {
            this.setProvider(provider);
        }
    };

    MM.Layer.prototype = {

        map: null, // TODO: remove
        parent: null,
        tiles: null,
        levels: null,

        requestManager: null,
        tileCacheSize: null,
        maxTileCacheSize: null,

        provider: null,
        recentTiles: null,
        recentTilesById: null,

        enablePyramidLoading: false,

        _tileComplete: null,

        getTileComplete: function() {
            if(!this._tileComplete) {
                var theLayer = this;
                this._tileComplete = function(manager, tile) {

                    // cache the tile itself:
                    theLayer.tiles[tile.id] = tile;
                    theLayer.tileCacheSize++;

                    // also keep a record of when we last touched this tile:
                    var record = {
                        id: tile.id,
                        lastTouchedTime: new Date().getTime()
                    };
                    theLayer.recentTilesById[tile.id] = record;
                    theLayer.recentTiles.push(record);

                    // position this tile (avoids a full draw() call):
                    theLayer.positionTile(tile);
                };
            }

            return this._tileComplete;
        },

        draw: function() {
            // if we're in between zoom levels, we need to choose the nearest:
            var baseZoom = Math.round(this.map.coordinate.zoom);

            // these are the top left and bottom right tile coordinates
            // we'll be loading everything in between:
            var startCoord = this.map.pointCoordinate(new MM.Point(0,0))
                .zoomTo(baseZoom).container();
            var endCoord = this.map.pointCoordinate(this.map.dimensions)
                .zoomTo(baseZoom).container().right().down();

            // tiles with invalid keys will be removed from visible levels
            // requests for tiles with invalid keys will be canceled
            // (this object maps from a tile key to a boolean)
            var validTileKeys = { };

            // make sure we have a container for tiles in the current level
            var levelElement = this.createOrGetLevel(startCoord.zoom);

            // use this coordinate for generating keys, parents and children:
            var tileCoord = startCoord.copy();

            for (tileCoord.column = startCoord.column;
                 tileCoord.column <= endCoord.column; tileCoord.column++) {
                for (tileCoord.row = startCoord.row;
                     tileCoord.row <= endCoord.row; tileCoord.row++) {
                    var validKeys = this.inventoryVisibleTile(levelElement, tileCoord);

                    while (validKeys.length) {
                        validTileKeys[validKeys.pop()] = true;
                    }
                }
            }

            // i from i to zoom-5 are levels that would be scaled too big,
            // i from zoom + 2 to levels. length are levels that would be
            // scaled too small (and tiles would be too numerous)
            for (var name in this.levels) {
                if (this.levels.hasOwnProperty(name)) {
                    var zoom = parseInt(name,10);

                    if (zoom >= startCoord.zoom-5 && zoom < startCoord.zoom+2) {
                        continue;
                    }

                    var level = this.levels[name];
                    level.style.display = 'none';
                    var visibleTiles = this.tileElementsInLevel(level);

                    while (visibleTiles.length) {
                        this.provider.releaseTile(visibleTiles[0].coord);
                        this.requestManager.clearRequest(visibleTiles[0].coord.toKey());
                        level.removeChild(visibleTiles[0]);
                        visibleTiles.shift();
                    }
                }
            }

            // levels we want to see, if they have tiles in validTileKeys
            var minLevel = startCoord.zoom - 5;
            var maxLevel = startCoord.zoom + 2;

            for (var z = minLevel; z < maxLevel; z++) {
                this.adjustVisibleLevel(this.levels[z], z, validTileKeys);
            }

            // cancel requests that aren't visible:
            this.requestManager.clearExcept(validTileKeys);

            // get newly requested tiles, sort according to current view:
            this.requestManager.processQueue(this.getCenterDistanceCompare());

            // make sure we don't have too much stuff:
            this.checkCache();
        },

        /**
         * For a given tile coordinate in a given level element, ensure that it's
         * correctly represented in the DOM including potentially-overlapping
         * parent and child tiles for pyramid loading.
         *
         * Return a list of valid (i.e. loadable?) tile keys.
         */
        inventoryVisibleTile: function(layer_element, tile_coord) {
            var tile_key = tile_coord.toKey(),
                valid_tile_keys = [tile_key];

            /*
             * Check that the needed tile already exists someplace - add it to the DOM if it does.
             */
            if (tile_key in this.tiles) {
                var tile = this.tiles[tile_key];

                // ensure it's in the DOM:
                if (tile.parentNode != layer_element) {
                    layer_element.appendChild(tile);
                    // if the provider implements reAddTile(), call it
                    if ("reAddTile" in this.provider) {
                        this.provider.reAddTile(tile_key, tile_coord, tile);
                    }
                }

                return valid_tile_keys;
            }

            /*
             * Check that the needed tile has even been requested at all.
             */
            if (!this.requestManager.hasRequest(tile_key)) {
                var tileToRequest = this.provider.getTile(tile_coord);
                if (typeof tileToRequest == 'string') {
                    this.addTileImage(tile_key, tile_coord, tileToRequest);
                // tile must be truish
                } else if (tileToRequest) {
                    this.addTileElement(tile_key, tile_coord, tileToRequest);
                }
            }

            // look for a parent tile in our image cache
            var tileCovered = false;
            var maxStepsOut = tile_coord.zoom;

            for (var pz = 1; pz <= maxStepsOut; pz++) {
                var parent_coord = tile_coord.zoomBy(-pz).container();
                var parent_key = parent_coord.toKey();

                if (this.enablePyramidLoading) {
                    // mark all parent tiles valid
                    valid_tile_keys.push(parent_key);
                    var parentLevel = this.createOrGetLevel(parent_coord.zoom);

                    //parentLevel.coordinate = parent_coord.copy();
                    if (parent_key in this.tiles) {
                        var parentTile = this.tiles[parent_key];
                        if (parentTile.parentNode != parentLevel) {
                            parentLevel.appendChild(parentTile);
                        }
                    } else if (!this.requestManager.hasRequest(parent_key)) {
                        // force load of parent tiles we don't already have
                        var tileToAdd = this.provider.getTile(parent_coord);

                        if (typeof tileToAdd == 'string') {
                            this.addTileImage(parent_key, parent_coord, tileToAdd);
                        } else {
                            this.addTileElement(parent_key, parent_coord, tileToAdd);
                        }
                    }
                } else {
                    // only mark it valid if we have it already
                    if (parent_key in this.tiles) {
                        valid_tile_keys.push(parent_key);
                        tileCovered = true;
                        break;
                    }
                }
            }

            // if we didn't find a parent, look at the children:
            if(!tileCovered && !this.enablePyramidLoading) {
                var child_coord = tile_coord.zoomBy(1);

                // mark everything valid whether or not we have it:
                valid_tile_keys.push(child_coord.toKey());
                child_coord.column += 1;
                valid_tile_keys.push(child_coord.toKey());
                child_coord.row += 1;
                valid_tile_keys.push(child_coord.toKey());
                child_coord.column -= 1;
                valid_tile_keys.push(child_coord.toKey());
            }

            return valid_tile_keys;
        },

        tileElementsInLevel: function(level) {
            // this is somewhat future proof, we're looking for DOM elements
            // not necessarily <img> elements
            var tiles = [];
            for(var tile = level.firstChild; tile; tile = tile.nextSibling) {
                if(tile.nodeType == 1) {
                    tiles.push(tile);
                }
            }
            return tiles;
        },

        /**
         * For a given level, adjust visibility as a whole and discard individual
         * tiles based on values in valid_tile_keys from inventoryVisibleTile().
         */
        adjustVisibleLevel: function(level, zoom, valid_tile_keys) {
            // for tracking time of tile usage:
            var now = new Date().getTime();

            if (!level) {
                // no tiles for this level yet
                return;
            }

            var scale = 1;
            var theCoord = this.map.coordinate.copy();

            if (level.childNodes.length > 0) {
                level.style.display = 'block';
                scale = Math.pow(2, this.map.coordinate.zoom - zoom);
                theCoord = theCoord.zoomTo(zoom);
            } else {
                level.style.display = 'none';
            }

            var tileWidth = this.map.tileSize.x * scale;
            var tileHeight = this.map.tileSize.y * scale;
            var center = new MM.Point(this.map.dimensions.x/2, this.map.dimensions.y/2);
            var tiles = this.tileElementsInLevel(level);

            while (tiles.length) {
                var tile = tiles.pop();

                if (!valid_tile_keys[tile.id]) {
                    this.provider.releaseTile(tile.coord);
                    this.requestManager.clearRequest(tile.coord.toKey());
                    level.removeChild(tile);
                } else {
                    // position tiles
                    MM.moveElement(tile, {
                        x: Math.round(center.x +
                            (tile.coord.column - theCoord.column) * tileWidth),
                        y: Math.round(center.y +
                            (tile.coord.row - theCoord.row) * tileHeight),
                        scale: scale,
                        // TODO: pass only scale or only w/h
                        width: this.map.tileSize.x,
                        height: this.map.tileSize.y
                    });

                    // log last-touched-time of currently cached tiles
                    this.recentTilesById[tile.id].lastTouchedTime = now;
                }
            }
        },

        createOrGetLevel: function(zoom) {
            if (zoom in this.levels) {
                return this.levels[zoom];
            }

            //console.log('creating level ' + zoom);
            var level = document.createElement('div');
            level.id = this.parent.id+'-zoom-'+zoom;
            level.style.cssText = this.parent.style.cssText;
            level.style.zIndex = zoom;
            this.parent.appendChild(level);
            this.levels[zoom] = level;
            return level;
        },

        addTileImage: function(key, coord, url) {
            this.requestManager.requestTile(key, coord, url);
        },

        addTileElement: function(key, coordinate, element) {
            // Expected in draw()
            element.id = key;
            element.coord = coordinate.copy();

            // cache the tile itself:
            this.tiles[key] = element;
            this.tileCacheSize++;

            // also keep a record of when we last touched this tile:
            var record = {
                id: key,
                lastTouchedTime: new Date().getTime()
            };
            this.recentTilesById[key] = record;
            this.recentTiles.push(record);

            this.positionTile(element);
        },

        positionTile: function(tile) {
            // position this tile (avoids a full draw() call):
            var theCoord = this.map.coordinate.zoomTo(tile.coord.zoom);
            var scale = Math.pow(2, this.map.coordinate.zoom - tile.coord.zoom);

            // Start tile positioning and prevent drag for modern browsers
            tile.style.cssText = 'position:absolute;-webkit-user-select: none;-webkit-user-drag: none;-moz-user-drag: none;';

            // Prevent drag for IE
            tile.ondragstart = function() { return false; };

            var scale = Math.pow(2, this.map.coordinate.zoom - tile.coord.zoom);
            var tx = ((this.map.dimensions.x/2) +
                (tile.coord.column - theCoord.column) *
                this.map.tileSize.x * scale);
            var ty = ((this.map.dimensions.y/2) +
                (tile.coord.row - theCoord.row) *
                this.map.tileSize.y * scale);

            MM.moveElement(tile, {
                x: Math.round(tx),
                y: Math.round(ty),
                scale: scale,
                // TODO: pass only scale or only w/h
                width: this.map.tileSize.x,
                height: this.map.tileSize.y
            });

            // add tile to its level
            var theLevel = this.levels[tile.coord.zoom];
            theLevel.appendChild(tile);

            // Support style transition if available.
            tile.className = 'map-tile-loaded';

            // ensure the level is visible if it's still the current level
            if (Math.round(this.map.coordinate.zoom) == tile.coord.zoom) {
                theLevel.style.display = 'block';
            }

            // request a lazy redraw of all levels
            // this will remove tiles that were only visible
            // to cover this tile while it loaded:
            this.requestRedraw();
        },

        _redrawTimer: undefined,

        requestRedraw: function() {
            // we'll always draw within 1 second of this request,
            // sometimes faster if there's already a pending redraw
            // this is used when a new tile arrives so that we clear
            // any parent/child tiles that were only being displayed
            // until the tile loads at the right zoom level
            if (!this._redrawTimer) {
                this._redrawTimer = setTimeout(this.getRedraw(), 1000);
            }
        },

        _redraw: null,

        getRedraw: function() {
            // let's only create this closure once...
            if (!this._redraw) {
                var theLayer = this;
                this._redraw = function() {
                    theLayer.draw();
                    theLayer._redrawTimer = 0;
                };
            }
            return this._redraw;
        },

        // keeps cache below max size
        // (called every time we receive a new tile and add it to the cache)
        checkCache: function() {
            var numTilesOnScreen = this.parent.getElementsByTagName('img').length;
            var maxTiles = Math.max(numTilesOnScreen, this.maxTileCacheSize);

            if (this.tileCacheSize > maxTiles) {
                // sort from newest (highest) to oldest (lowest)
                this.recentTiles.sort(function(t1, t2) {
                    return t2.lastTouchedTime < t1.lastTouchedTime ? -1 :
                      t2.lastTouchedTime > t1.lastTouchedTime ? 1 : 0;
                });
            }

            while (this.tileCacheSize > maxTiles) {
                // delete the oldest record
                var tileRecord = this.recentTiles.pop();
                var now = new Date().getTime();
                delete this.recentTilesById[tileRecord.id];
                //window.console.log('removing ' + tileRecord.id +
                //                   ' last seen ' + (now-tileRecord.lastTouchedTime) + 'ms ago');
                // now actually remove it from the cache...
                var tile = this.tiles[tileRecord.id];
                if (tile.parentNode) {
                    // I'm leaving this uncommented for now but you should never see it:
                    alert("Gah: trying to removing cached tile even though it's still in the DOM");
                } else {
                    delete this.tiles[tileRecord.id];
                    this.tileCacheSize--;
                }
            }
        },

        setProvider: function(newProvider) {
            if ('getTileUrl' in newProvider && (typeof newProvider.getTileUrl === 'function')) {
                newProvider = new MM.TilePaintingProvider(newProvider);
            }

            var firstProvider = (this.provider === null);

            // if we already have a provider the we'll need to
            // clear the DOM, cancel requests and redraw
            if (!firstProvider) {
                this.requestManager.clear();

                for (var name in this.levels) {
                    if (this.levels.hasOwnProperty(name)) {
                        var level = this.levels[name];

                        while (level.firstChild) {
                            this.provider.releaseTile(level.firstChild.coord);
                            level.removeChild(level.firstChild);
                        }
                    }
                }
            }

            // first provider or not we'll init/reset some values...

            this.tiles = {};
            this.tileCacheSize = 0;
            this.maxTileCacheSize = 64;
            this.recentTilesById = {};
            this.recentTiles = [];

            // for later: check geometry of old provider and set a new coordinate center
            // if needed (now? or when?)

            this.provider = newProvider;

            if (!firstProvider) {
                this.draw();
            }
        },

        // compares manhattan distance from center of
        // requested tiles to current map center
        // NB:- requested tiles are *popped* from queue, so we do a descending sort
        getCenterDistanceCompare: function() {
            var theCoord = this.map.coordinate.zoomTo(Math.round(this.map.coordinate.zoom));

            return function(r1, r2) {
                if (r1 && r2) {
                    var c1 = r1.coord;
                    var c2 = r2.coord;
                    if (c1.zoom == c2.zoom) {
                        var ds1 = Math.abs(theCoord.row - c1.row - 0.5) +
                                  Math.abs(theCoord.column - c1.column - 0.5);
                        var ds2 = Math.abs(theCoord.row - c2.row - 0.5) +
                                  Math.abs(theCoord.column - c2.column - 0.5);
                        return ds1 < ds2 ? 1 : ds1 > ds2 ? -1 : 0;
                    } else {
                        return c1.zoom < c2.zoom ? 1 : c1.zoom > c2.zoom ? -1 : 0;
                    }
                }
                return r1 ? 1 : r2 ? -1 : 0;
            };
        },

        destroy: function() {
            this.requestManager.clear();
            this.requestManager.removeCallback('requestcomplete', this.getTileComplete());
            // TODO: does requestManager need a destroy function too?
            this.provider = null;
            // If this layer was ever attached to the DOM, detach it.
            if (this.parent.parentNode) {
              this.parent.parentNode.removeChild(this.parent);
            }
            this.map = null;
        }

    };

    // Map

    // Instance of a map intended for drawing to a div.
    //
    //  * `parent` (required DOM element)
    //      Can also be an ID of a DOM element
    //  * `layerOrLayers` (required MM.Layer or Array of MM.Layers)
    //      each one must implement draw(), destroy(), have a .parent DOM element and a .map property
    //      (an array of URL templates or MM.MapProviders is also acceptable)
    //  * `dimensions` (optional Point)
    //      Size of map to create
    //  * `eventHandlers` (optional Array)
    //      If empty or null MouseHandler will be used
    //      Otherwise, each handler will be called with init(map)
    MM.Map = function(parent, layerOrLayers, dimensions, eventHandlers) {

        if (typeof parent == 'string') {
            parent = document.getElementById(parent);
            if (!parent) {
                throw 'The ID provided to modest maps could not be found.';
            }
        }
        this.parent = parent;

        // we're no longer adding width and height to parent.style but we still
        // need to enforce padding, overflow and position otherwise everything screws up
        // TODO: maybe console.warn if the current values are bad?
        this.parent.style.padding = '0';
        this.parent.style.overflow = 'hidden';

        var position = MM.getStyle(this.parent, 'position');
        if (position != 'relative' && position != 'absolute') {
            this.parent.style.position = 'relative';
        }

        this.layers = [];
        if(!(layerOrLayers instanceof Array)) {
            layerOrLayers = [ layerOrLayers ];
        }

        for (var i = 0; i < layerOrLayers.length; i++) {
            this.addLayer(layerOrLayers[i]);
        }

        // default to Google-y Mercator style maps
        this.projection = new MM.MercatorProjection(0,
            MM.deriveTransformation(-Math.PI,  Math.PI, 0, 0,
                                     Math.PI,  Math.PI, 1, 0,
                                    -Math.PI, -Math.PI, 0, 1));
        this.tileSize = new MM.Point(256, 256);

        // default 0-18 zoom level
        // with infinite horizontal pan and clamped vertical pan
        this.coordLimits = [
            new MM.Coordinate(0,-Infinity,0),           // top left outer
            new MM.Coordinate(1,Infinity,0).zoomTo(18) // bottom right inner
        ];

        // eyes towards null island
        this.coordinate = new MM.Coordinate(0.5, 0.5, 0);

        // if you don't specify dimensions we assume you want to fill the parent
        // unless the parent has no w/h, in which case we'll still use a default
        if (!dimensions) {
            dimensions = new MM.Point(this.parent.offsetWidth,
                                      this.parent.offsetHeight);
            this.autoSize = true;
            // use destroy to get rid of this handler from the DOM
            MM.addEvent(window, 'resize', this.windowResize());
        } else {
            this.autoSize = false;
            // don't call setSize here because it calls draw()
            this.parent.style.width = Math.round(dimensions.x) + 'px';
            this.parent.style.height = Math.round(dimensions.y) + 'px';
        }
        this.dimensions = dimensions;

        this.callbackManager = new MM.CallbackManager(this, [
            'zoomed',
            'panned',
            'centered',
            'extentset',
            'resized',
            'drawn'
        ]);

        // set up handlers last so that all required attributes/functions are in place if needed
        if (eventHandlers === undefined) {
            this.eventHandlers = [
                new MM.MouseHandler(this),
                new MM.TouchHandler(this)
            ];
        } else {
            this.eventHandlers = eventHandlers;
            if (eventHandlers instanceof Array) {
                for (var j = 0; j < eventHandlers.length; j++) {
                    eventHandlers[j].init(this);
                }
            }
        }

    };

    MM.Map.prototype = {

        parent: null,          // DOM Element
        dimensions: null,      // MM.Point with x/y size of parent element

        projection: null,      // MM.Projection of first known layer
        coordinate: null,      // Center of map MM.Coordinate with row/column/zoom
        tileSize: null,        // MM.Point with x/y size of tiles

        coordLimits: null,     // Array of [ topLeftOuter, bottomLeftInner ] MM.Coordinates

        layers: null,          // Array of MM.Layer (interface = .draw(), .destroy(), .parent and .map)

        callbackManager: null, // MM.CallbackManager, handles map events

        eventHandlers: null,   // Array of interaction handlers, just a MM.MouseHandler by default

        autoSize: null,        // Boolean, true if we have a window resize listener

        toString: function() {
            return 'Map(#' + this.parent.id + ')';
        },

        // callbacks...

        addCallback: function(event, callback) {
            this.callbackManager.addCallback(event, callback);
            return this;
        },

        removeCallback: function(event, callback) {
            this.callbackManager.removeCallback(event, callback);
            return this;
        },

        dispatchCallback: function(event, message) {
            this.callbackManager.dispatchCallback(event, message);
            return this;
        },

        windowResize: function() {
            if (!this._windowResize) {
                var theMap = this;
                this._windowResize = function(event) {
                    // don't call setSize here because it sets parent.style.width/height
                    // and setting the height breaks percentages and default styles
                    theMap.dimensions = new MM.Point(theMap.parent.offsetWidth, theMap.parent.offsetHeight);
                    theMap.draw();
                    theMap.dispatchCallback('resized', [theMap.dimensions]);
                };
            }
            return this._windowResize;
        },

        // A convenience function to restrict interactive zoom ranges.
        // (you should also adjust map provider to restrict which tiles get loaded,
        // or modify map.coordLimits and provider.tileLimits for finer control)
        setZoomRange: function(minZoom, maxZoom) {
            this.coordLimits[0] = this.coordLimits[0].zoomTo(minZoom);
            this.coordLimits[1] = this.coordLimits[1].zoomTo(maxZoom);
        },

        // zooming
        zoomBy: function(zoomOffset) {
            this.coordinate = this.enforceLimits(this.coordinate.zoomBy(zoomOffset));
            MM.getFrame(this.getRedraw());
            this.dispatchCallback('zoomed', zoomOffset);
            return this;
        },

        zoomIn: function()  { return this.zoomBy(1); },
        zoomOut: function()  { return this.zoomBy(-1); },
        setZoom: function(z) { return this.zoomBy(z - this.coordinate.zoom); },

        zoomByAbout: function(zoomOffset, point) {
            var location = this.pointLocation(point);

            this.coordinate = this.enforceLimits(this.coordinate.zoomBy(zoomOffset));
            var newPoint = this.locationPoint(location);

            this.dispatchCallback('zoomed', zoomOffset);
            return this.panBy(point.x - newPoint.x, point.y - newPoint.y);
        },

        // panning
        panBy: function(dx, dy) {
            this.coordinate.column -= dx / this.tileSize.x;
            this.coordinate.row -= dy / this.tileSize.y;

            this.coordinate = this.enforceLimits(this.coordinate);

            // Defer until the browser is ready to draw.
            MM.getFrame(this.getRedraw());
            this.dispatchCallback('panned', [dx, dy]);
            return this;
        },

        /*
        panZoom: function(dx, dy, zoom) {
            this.coordinate.column -= dx / this.tileSize.x;
            this.coordinate.row -= dy / this.tileSize.y;
            this.coordinate = this.coordinate.zoomTo(zoom);

            // Defer until the browser is ready to draw.
            MM.getFrame(this.getRedraw());
            this.dispatchCallback('panned', [dx, dy]);
            return this;
        },
        */

        panLeft: function() { return this.panBy(100, 0); },
        panRight: function() { return this.panBy(-100, 0); },
        panDown: function() { return this.panBy(0, -100); },
        panUp: function() { return this.panBy(0, 100); },

        // positioning
        setCenter: function(location) {
            return this.setCenterZoom(location, this.coordinate.zoom);
        },

        setCenterZoom: function(location, zoom) {
            this.coordinate = this.projection.locationCoordinate(location).zoomTo(parseFloat(zoom) || 0);
            MM.getFrame(this.getRedraw());
            this.dispatchCallback('centered', [location, zoom]);
            return this;
        },

        setExtent: function(locations, precise) {
            // coerce locations to an array if it's a MapExtent instance
            if (locations instanceof MM.MapExtent) {
                locations = locations.toArray();
            }

            var TL, BR;
            for (var i = 0; i < locations.length; i++) {
                var coordinate = this.projection.locationCoordinate(locations[i]);
                if (TL) {
                    TL.row = Math.min(TL.row, coordinate.row);
                    TL.column = Math.min(TL.column, coordinate.column);
                    TL.zoom = Math.min(TL.zoom, coordinate.zoom);
                    BR.row = Math.max(BR.row, coordinate.row);
                    BR.column = Math.max(BR.column, coordinate.column);
                    BR.zoom = Math.max(BR.zoom, coordinate.zoom);
                }
                else {
                    TL = coordinate.copy();
                    BR = coordinate.copy();
                }
            }

            var width = this.dimensions.x + 1;
            var height = this.dimensions.y + 1;

            // multiplication factor between horizontal span and map width
            var hFactor = (BR.column - TL.column) / (width / this.tileSize.x);

            // multiplication factor expressed as base-2 logarithm, for zoom difference
            var hZoomDiff = Math.log(hFactor) / Math.log(2);

            // possible horizontal zoom to fit geographical extent in map width
            var hPossibleZoom = TL.zoom - (precise ? hZoomDiff : Math.ceil(hZoomDiff));

            // multiplication factor between vertical span and map height
            var vFactor = (BR.row - TL.row) / (height / this.tileSize.y);

            // multiplication factor expressed as base-2 logarithm, for zoom difference
            var vZoomDiff = Math.log(vFactor) / Math.log(2);

            // possible vertical zoom to fit geographical extent in map height
            var vPossibleZoom = TL.zoom - (precise ? vZoomDiff : Math.ceil(vZoomDiff));

            // initial zoom to fit extent vertically and horizontally
            var initZoom = Math.min(hPossibleZoom, vPossibleZoom);

            // additionally, make sure it's not outside the boundaries set by map limits
            initZoom = Math.min(initZoom, this.coordLimits[1].zoom);
            initZoom = Math.max(initZoom, this.coordLimits[0].zoom);

            // coordinate of extent center
            var centerRow = (TL.row + BR.row) / 2;
            var centerColumn = (TL.column + BR.column) / 2;
            var centerZoom = TL.zoom;

            this.coordinate = new MM.Coordinate(centerRow, centerColumn, centerZoom).zoomTo(initZoom);
            this.draw(); // draw calls enforceLimits
            // (if you switch to getFrame, call enforceLimits first)

            this.dispatchCallback('extentset', locations);
            return this;
        },

        // Resize the map's container `<div>`, redrawing the map and triggering
        // `resized` to make sure that the map's presentation is still correct.
        setSize: function(dimensions) {
            // Ensure that, whether a raw object or a Point object is passed,
            // this.dimensions will be a Point.
            this.dimensions = new MM.Point(dimensions.x, dimensions.y);
            this.parent.style.width = Math.round(this.dimensions.x) + 'px';
            this.parent.style.height = Math.round(this.dimensions.y) + 'px';
            if (this.autoSize) {
                MM.removeEvent(window, 'resize', this.windowResize());
                this.autoSize = false;
            }
            this.draw(); // draw calls enforceLimits
            // (if you switch to getFrame, call enforceLimits first)
            this.dispatchCallback('resized', this.dimensions);
            return this;
        },

        // projecting points on and off screen
        coordinatePoint: function(coord) {
            // Return an x, y point on the map image for a given coordinate.
            if (coord.zoom != this.coordinate.zoom) {
                coord = coord.zoomTo(this.coordinate.zoom);
            }

            // distance from the center of the map
            var point = new MM.Point(this.dimensions.x / 2, this.dimensions.y / 2);
            point.x += this.tileSize.x * (coord.column - this.coordinate.column);
            point.y += this.tileSize.y * (coord.row - this.coordinate.row);

            return point;
        },

        // Get a `MM.Coordinate` from an `MM.Point` - returns a new tile-like object
        // from a screen point.
        pointCoordinate: function(point) {
            // new point coordinate reflecting distance from map center, in tile widths
            var coord = this.coordinate.copy();
            coord.column += (point.x - this.dimensions.x / 2) / this.tileSize.x;
            coord.row += (point.y - this.dimensions.y / 2) / this.tileSize.y;

            return coord;
        },

        // Return an MM.Coordinate (row,col,zoom) for an MM.Location (lat,lon).
        locationCoordinate: function(location) {
            return this.projection.locationCoordinate(location);
        },

        // Return an MM.Location (lat,lon) for an MM.Coordinate (row,col,zoom).
        coordinateLocation: function(coordinate) {
            return this.projection.coordinateLocation(coordinate);
        },

        // Return an x, y point on the map image for a given geographical location.
        locationPoint: function(location) {
            return this.coordinatePoint(this.locationCoordinate(location));
        },

        // Return a geographical location on the map image for a given x, y point.
        pointLocation: function(point) {
            return this.coordinateLocation(this.pointCoordinate(point));
        },

        // inspecting
        getExtent: function() {
            var extent = [];
            extent.push(this.pointLocation(new MM.Point(0, 0)));
            extent.push(this.pointLocation(this.dimensions));
            return extent;
        },

        extent: function(locations, precise) {
            if (locations) {
                return this.setExtent(locations, precise);
            } else {
                return this.getExtent();
            }
        },

        // Get the current centerpoint of the map, returning a `Location`
        getCenter: function() {
            return this.projection.coordinateLocation(this.coordinate);
        },

        center: function(location) {
            if (location) {
                return this.setCenter(location);
            } else {
                return this.getCenter();
            }
        },

        // Get the current zoom level of the map, returning a number
        getZoom: function() {
            return this.coordinate.zoom;
        },

        zoom: function(zoom) {
            if (zoom !== undefined) {
                return this.setZoom(zoom);
            } else {
                return this.getZoom();
            }
        },

        // layers
        // HACK for 0.x.y - stare at @RandomEtc 
        // this method means we can also pass a URL template or a MapProvider to addLayer
        coerceLayer: function(layerish) {
            if ('draw' in layerish && typeof layerish.draw == 'function') {
                // good enough, though we should probably enforce .parent and .destroy() too
                return layerish;
            } else if (typeof layerish == 'string') {
                // probably a template string
                return new MM.Layer(new MM.TemplatedMapProvider(layerish));
            } else {
                // probably a MapProvider
                return new MM.Layer(layerish);
            }
        },

        // return a copy of the layers array
        getLayers: function() {
            return this.layers.slice();
        },

        // return the layer at the given index
        getLayerAt: function(index) {
            return this.layers[index];
        },

        // put the given layer on top of all the others
        addLayer: function(layer) {
            layer = this.coerceLayer(layer);
            this.layers.push(layer);
            // make sure layer.parent doesn't already have a parentNode
            if (!layer.parent.parentNode) {
                this.parent.appendChild(layer.parent); 
            }
            layer.map = this; // TODO: remove map property from MM.Layer?
            return this;
        },

        // find the given layer and remove it
        removeLayer: function(layer) {
            for (var i = 0; i < this.layers.length; i++) {
                if (layer == this.layers[i]) {
                    this.removeLayerAt(i);
                    break;
                }
            }
            return this;
        },

        // replace the current layer at the given index with the given layer
        setLayerAt: function(index, layer) {

            if (index < 0 || index >= this.layers.length) {
                throw new Error('invalid index in setLayerAt(): ' + index);
            }

            layer = this.coerceLayer(layer);

            if (this.layers[index] != layer) {

                // clear existing layer at this index
                if (index < this.layers.length) {
                    this.layers[index].destroy();
                }

                // pass it on.
                this.layers[index] = layer;
                this.parent.appendChild(layer.parent);
                layer.map = this; // TODO: remove map property from MM.Layer

                MM.getFrame(this.getRedraw());
            }

            return this;
        },

        // put the given layer at the given index, moving others if necessary
        insertLayerAt: function(index, layer) {

            if (index < 0 || index > this.layers.length) {
                throw new Error('invalid index in insertLayerAt(): ' + index);
            }

            layer = this.coerceLayer(layer);

            if(index == this.layers.length) {
                // it just gets tacked on to the end
                this.layers.push(layer);
                this.parent.appendChild(layer.parent);
            } else {
                // it needs to get slipped in amongst the others
                var other = this.layers[index];
                this.parent.insertBefore(layer.parent, other.parent);
                this.layers.splice(index, 0, layer);
            }

            layer.map = this; // TODO: remove map property from MM.Layer

            MM.getFrame(this.getRedraw());

            return this;
        },

        // remove the layer at the given index, call .destroy() on the layer
        removeLayerAt: function(index) {
            if (index < 0 || index >= this.layers.length) {
                throw new Error('invalid index in removeLayer(): ' + index);
            }

            // gone baby gone.
            var old = this.layers[index];
            this.layers.splice(index, 1);
            old.destroy();

            return this;
        },

        // switch the stacking order of two layers, by index
        swapLayersAt: function(i, j) {

            if (i < 0 || i >= this.layers.length || j < 0 || j >= this.layers.length) {
                throw new Error('invalid index in swapLayersAt(): ' + index);
            }

            var layer1 = this.layers[i],
                layer2 = this.layers[j],
                dummy = document.createElement('div');

            // kick layer2 out, replace it with the dummy.
            this.parent.replaceChild(dummy, layer2.parent);

            // put layer2 back in and kick layer1 out
            this.parent.replaceChild(layer2.parent, layer1.parent);

            // put layer1 back in and ditch the dummy
            this.parent.replaceChild(layer1.parent, dummy);

            // now do it to the layers array
            this.layers[i] = layer2;
            this.layers[j] = layer1;

            return this;
        },

        // limits

        enforceZoomLimits: function(coord) {
            var limits = this.coordLimits;
            if (limits) {
                // clamp zoom level:
                var minZoom = limits[0].zoom;
                var maxZoom = limits[1].zoom;
                if (coord.zoom < minZoom) {
                    coord = coord.zoomTo(minZoom);
                }
                else if (coord.zoom > maxZoom) {
                    coord = coord.zoomTo(maxZoom);
                }
            }
            return coord;
        },

        enforcePanLimits: function(coord) {

            var limits = this.coordLimits;

            if (limits) {

                coord = coord.copy();

                // clamp pan:
                var topLeftLimit = limits[0].zoomTo(coord.zoom);
                var bottomRightLimit = limits[1].zoomTo(coord.zoom);
                var currentTopLeft = this.pointCoordinate(new MM.Point(0,0));
                var currentBottomRight = this.pointCoordinate(this.dimensions);

                // this handles infinite limits:
                // (Infinity - Infinity) is Nan
                // NaN is never less than anything
                if (bottomRightLimit.row - topLeftLimit.row < currentBottomRight.row - currentTopLeft.row) {
                    // if the limit is smaller than the current view center it
                    coord.row = (bottomRightLimit.row + topLeftLimit.row) / 2;
                }
                else {
                    if (currentTopLeft.row < topLeftLimit.row) {
                        coord.row += topLeftLimit.row - currentTopLeft.row;
                    }
                    else if (currentBottomRight.row > bottomRightLimit.row) {
                        coord.row -= currentBottomRight.row - bottomRightLimit.row;
                    }
                }
                if (bottomRightLimit.column - topLeftLimit.column < currentBottomRight.column - currentTopLeft.column) {
                    // if the limit is smaller than the current view, center it
                    coord.column = (bottomRightLimit.column + topLeftLimit.column) / 2;
                }
                else {
                    if (currentTopLeft.column < topLeftLimit.column) {
                        coord.column += topLeftLimit.column - currentTopLeft.column;
                    }
                    else if (currentBottomRight.column > bottomRightLimit.column) {
                        coord.column -= currentBottomRight.column - bottomRightLimit.column;
                    }
                }
            }

            return coord;
        },

        // Prevent accidentally navigating outside the `coordLimits` of the map.
        enforceLimits: function(coord) {
            return this.enforcePanLimits(this.enforceZoomLimits(coord));
        },

        // rendering

        // Redraw the tiles on the map, reusing existing tiles.
        draw: function() {
            // make sure we're not too far in or out:
            this.coordinate = this.enforceLimits(this.coordinate);

            // if we don't have dimensions, check the parent size
            if (this.dimensions.x <= 0 || this.dimensions.y <= 0) {
                if (this.autoSize) {
                    // maybe the parent size has changed?
                    var w = this.parent.offsetWidth,
                        h = this.parent.offsetHeight;
                    this.dimensions = new MM.Point(w,h);
                    if (w <= 0 || h <= 0) {
                        return;
                    }
                } else {
                    // the issue can only be corrected with setSize
                    return;
                }
            }

            // draw layers one by one
            for(var i = 0; i < this.layers.length; i++) {
                this.layers[i].draw();
            }

            this.dispatchCallback('drawn');
        },

        _redrawTimer: undefined,

        requestRedraw: function() {
            // we'll always draw within 1 second of this request,
            // sometimes faster if there's already a pending redraw
            // this is used when a new tile arrives so that we clear
            // any parent/child tiles that were only being displayed
            // until the tile loads at the right zoom level
            if (!this._redrawTimer) {
                this._redrawTimer = setTimeout(this.getRedraw(), 1000);
            }
        },

        _redraw: null,

        getRedraw: function() {
            // let's only create this closure once...
            if (!this._redraw) {
                var theMap = this;
                this._redraw = function() {
                    theMap.draw();
                    theMap._redrawTimer = 0;
                };
            }
            return this._redraw;
        },

        // Attempts to destroy all attachment a map has to a page
        // and clear its memory usage.
        destroy: function() {
            for (var j = 0; j < this.layers.length; j++) {
                this.layers[j].destroy();
            }
            this.layers = [];
            this.projection = null;
            for (var i = 0; i < this.eventHandlers.length; i++) {
                this.eventHandlers[i].remove();
            }
            if (this.autoSize) {
                MM.removeEvent(window, 'resize', this.windowResize());
            }
        }
    };
    if (typeof module !== 'undefined' && module.exports) {
      module.exports = {
          Point: MM.Point,
          Projection: MM.Projection,
          MercatorProjection: MM.MercatorProjection,
          LinearProjection: MM.LinearProjection,
          Transformation: MM.Transformation,
          Location: MM.Location,
          MapProvider: MM.MapProvider,
          TemplatedMapProvider: MM.TemplatedMapProvider,
          Coordinate: MM.Coordinate,
          deriveTransformation: MM.deriveTransformation
      };
    }
})(MM);
if (typeof HTMAPL === "undefined") var HTMAPL = {};
(function() {

    // TODO: include minified (and hacked) modestmaps.js and modestmaps.markers.js here?
    try {
        var MM = MM || com.modestmaps;
    } catch (e) {
        throw "Couldn't find MM or com.modestmaps. Did you include modestmaps.js?";
        return false;
    }

    var DEFAULTS = HTMAPL.defaults = {
        "map": {
            "center":       {lat: 37.764, lon: -122.419},
            "zoom":         1,
            "extent":       null,
            "provider":     "toner",
            "interactive":  "true",
            "mousewheel":   "false",
            "touch":        "false",
            "mousezoom":    null,
            "hash":         "false",
            "layers":       ".layer",
            "markers":      ".marker",
            "controls":     ".controls"
        },
        "layer": {
            "type":         "image",
            "provider":     null,
            "url":          null,
            "data_type":     "json",
            "template":     null,
            "set_extent":    "false"
        }
    };

    var ATTRIBUTES = HTMAPL.dataAttributes = {
        // map option parsers
        "map": {
            "center":       "latLon",
            "zoom":         "integer",
            "extent":       "extent",
            "provider":     "provider",
            "interactive":  "boolean",
            "mousewheel":   "boolean",
            "mousezoom":    "numarray",
            "touch":        "boolean",
            "hash":         "boolean",
            "layers":       String,
            "markers":      String,
            "controls":     String
        },
        // layer option parsers
        "layer": {
            "type":         String,
            "provider":     "provider",
            "url":          String,
            "data_type":    String,
            "template":     String,
            "set_extent":   "boolean"
        }
    };

    HTMAPL.Map = function(element, defaults) {
        this.initialize(element, defaults);
    };

    /**
     * The Map looks for options in an element, merges those with any
     * provided in the constructor, builds data and marker layers, and provides
     * an applyOptions() method for setting any post-initialization options.
     */
    HTMAPL.Map.prototype = {

        /**
         * initialize() takes an optional hash of option defaults, which
         * are merged together with HTMAPL.defaults.map to form the set of
         * options before applying any additional ones found in the DOM.
         */
        initialize: function(element, defaults) {
            // Create the map. By default our provider is empty.
            MM.Map.call(this, element, NULL_PROVIDER, null, []);

            var options = {};

            // merge in gobal defaults, then user-provided defaults
            extend(options, DEFAULTS.map, defaults);
            // console.log("options:", JSON.stringify(options));
            // parse options out of the DOM element and include those
            this.parseOptions(options, this.parent, ATTRIBUTES.map);
            // console.log("options:", JSON.stringify(options));

            if (options.mousezoom) {
                var zoomHandler = new MM.MouseMoveZoomHandler(this, options.mousezoom);
                this.eventHandlers.push(zoomHandler);
                // use the outer zoom as the default
                options.zoom = zoomHandler.outerZoom;
            } else if (options.interactive) {
                // if the "interactive" option is set, include the MouseHandler
                this.eventHandlers.push(new MM.DragHandler(this));
                this.eventHandlers.push(new MM.DoubleClickHandler(this));
                if (options.mousewheel) {
                    // TODO: precise argument for intermediate zooms
                    this.eventHandlers.push(new MM.MouseWheelHandler(this));
                }

                if (options.touch) {
                    this.eventHandlers.push(new MM.TouchHandler(this));
                }
            }

            // intialize data and marker layers
            if (options.layers) {
                this.initLayers(options.layers);
            }

            // additionally, intialize markers as their own layer
            if (options.markers) {
                this.initMarkers(options.markers);
            }

            if (options.controls) {
                this.initControls(options.controls);
            }

            // then apply the runtime options: center, zoom, extent, provider
            this._applyParsedOptions(options);

            if (options.hash === true) {
                this.eventHandlers.push(new MM.Hash(this));
            } else {
                console.log("hash?", options.hash);
            }
        },

        /**
         * Initialize markers as their own layer.
         */
        initMarkers: function(filter) {
            var markers = this.getChildren(this.parent, filter);
            if (markers.length) {
                var div = document.createElement("div"),
                    markerLayer = new MM.MarkerLayer(div);
                this.addLayerMarkers(markerLayer, markers);
                this.addLayer(markerLayer);

                this.markers = markerLayer;
                return markerLayer;
            }
            return null;
        },

        /**
         * Adds each marker element in a jQuery selection to the provided
         * ModestMaps Layer. Each marker element should have a "location" data
         * that getLatLon() can parse into a Location object.
         *
         * Returns the number of markers added with valid locations.
         */
        addLayerMarkers: function(layer, markers) {
            var added = 0,
                len = markers.length;
            for (var i = 0; i < len; i++) {
                var marker = markers[i],
                    rawLocation = this.getData(marker, "location"),
                    parsedLocation = PARSE.latLon(rawLocation);
                if (parsedLocation) {
                    layer.addMarker(marker, parsedLocation);
                    added++;
                } else {
                    console.warn("invalid marker location:", rawLocation, "; skipping marker:", marker);
                }
            }
            return added;
        },

        initLayers: function(filter) {
            var children = this.getChildren(this.parent, filter),
                len = children.length;
            for (var i = 0; i < len; i++) {
                var layer = children[i],
                    layerOptions = {};
                extend(layerOptions, DEFAULTS.layer);
                // console.log("(init) layer options:", layerOptions);

                this.parseOptions(layerOptions, layer, ATTRIBUTES.layer);

                // console.log("(parsed) layer options:", layerOptions);

                var type = layerOptions.type,
                    provider = layerOptions.provider;

                if (!type) {
                    console.warn("no type defined for layer:", layer);
                    continue;
                }

                // console.log("  + layer:", [type, provider], layer);

                var mapLayer;
                switch (type.toLowerCase()) {
                    case "markers":
                        // marker layers ignore the provider
                        mapLayer = new MM.MarkerLayer(layer);
                        this.addLayerMarkers(mapLayer, this.getChildren(layer));
                        break;

                    case "geojson":
                        var url = layerOptions.url || layerOptions.provider,
                            template = layerOptions.template,
                            tiled = url && url.match(/{(Z|X|Y)}/);

                        if (!url) {
                            console.warn("no URL/provider found for GeoJSON layer:", layer);
                            continue;
                        }

                        // console.log("template:", template);

                        var buildMarker;
                        switch (typeof template) {
                            case "function":
                                buildMarker = template;
                                break;
                            case "string":
                                buildMarker = this.getBuildMarker(template);
                                break;
                        }

                        // console.log("buildMarker:", buildMarker);

                        // XXX: two very different things happen here
                        // depending on whether the data is "tiled":
                        if (tiled) {

                            // if so, we use a GeoJSONProvider and a tiled
                            // layer...
                            if (provider) {
                                mapProvider = new MM.GeoJSONProvider(provider, buildMarker);
                                mapLayer = new MM.Layer(mapProvider, layer);
                            } else {
                                console.warn("no GeoJSON provider found for:", [url], "on layer:", layer);
                                continue;
                            }

                        } else {

                            // otherwise we create a MarkerLayer, load
                            // data, and add markers on success.
                            mapLayer = new MM.MarkerLayer(layer);

                            /**
                             * XXX:
                             * The AJAX request options follow jQuery.ajax()
                             * conventions. Currently the only option that we
                             * support is "dataType", which is assumed to be
                             * either "json" (subject to cross-origin security
                             * restrictions) or "jsonp" (which uses callbacks
                             * and is not subject to CORS restrictions).
                             */
                            var requestOptions = {
                                "dataType": layerOptions.data_type
                            };

                            // for the success closure
                            var map = this;

                            this.ajax(url, requestOptions, function(collection) {
                                var features = collection.features,
                                    len = features.length,
                                    locations = [];
                                for (var i = 0; i < len; i++) {
                                    var feature = features[i],
                                        marker = buildMarker.call(mapLayer, feature);
                                    mapLayer.addMarker(marker, feature);
                                    locations.push(marker.location);
                                }
                                if (locations.length && layerOptions.set_extent) {
                                    map.setExtent(locations);
                                } else {
                                    console.log("not setting extent:", layerOptions);
                                }
                            });
                        }
                        break;
                        
                    case "image":
                        if (!provider) {
                            console.warn("no provider found for image layer:", layer, layerOptions);
                            break;
                        }
                        mapLayer = new MM.Layer(provider, layer);
                        break;
                }

                if (mapLayer) {
                    this.addLayer(mapLayer);
                } else {
                    console.warn("no provider created for layer of type", type, ":", layer);
                }
            }
        },

        initControls: function(filter) {
            var controls = this.getChildren(this.parent, filter),
                len = controls.length;
            for (var i = 0; i < len; i++) {
                var ctrl = controls[i];
                // console.log("+ control group:", ctrl);
                if (this.isControl(ctrl)) {
                    this.initControl(ctrl);
                } else {
                    // console.log("  looking for children...", ctrl.childNodes.length);
                    var children = this.getChildren(ctrl, this.isControl),
                        clen = children.length;
                    for (var j = 0; j < clen; j++) {
                        this.initControl(children[j]);
                    }
                }
            }
        },

        isControl: function(element) {
            return element.nodeType == 1 && this.getData(element, "action");
        },

        initControl: function(element) {
            var map = this,
                action = this.getData(element, "action");
            // console.log("+ control:", element, action);

            if (action.indexOf("(") > -1) {
                function exec(e) {
                    with (map) { eval(action); }
                }
            } else {
                var args = action.split(":"),
                    name = args.shift();
                switch (name) {
                    case "setProvider":
                        // XXX: this is kind of ugly... join the args back together
                        args = [args.join(":")];
                        break;
                    case "setCenter":
                        args[0] = PARSE.latLon(args[0]);
                        break;
                    case "setCenterZoom":
                        if (args.length == 1) {
                            var cz = PARSE.centerZoom(args[0]);
                            if (cz) {
                                args[0] = cz.center;
                                args[1] = cz.zoom;
                            } else {
                                return null;
                            }
                        } else {
                            args[0] = PARSE.latLon(args[0]);
                            args[1] = PARSE.integer(args[1]);
                        }
                        break;
                    case "setZoom":
                        args[0] = PARSE.integer(args[0]);
                        break;
                    case "setExtent":
                        args[0] = PARSE.extent(args[0]);
                        break;
                }
                function exec(e) {
                    map[name].apply(map, args);
                }
            }

            // prevent double click events from bubbling up
            MM.addEvent(element, "dblclick", function(e) {
                try {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                } catch (e) {
                    console.warn("couldn't stop double-click: ", e);
                }
                return false;
            });

            // and execute the action on click
            // TODO: parse the action at runtime so it can be changed?
            MM.addEvent(element, "click", function(e) {
                // console.log("click:", element, e);
                try {
                    exec(e);
                    e.preventDefault();
                } catch (e) {
                    console.warn("failed to exec control: ", e);
                }
                return false;
            });
        },

        /**
         * Get a marker building function. This is assumed to be a symbol in
         * the global scope that can be evaluated with eval(). If the string
         * evaluates to anything other than a function, we return null.
         */
        getBuildMarker: function(name) {
            try {
                var ref;
                // TODO: replace eval() with a safe recursive lookup
                with (window) {
                    ref = eval(name);
                }
                if (typeof ref === "function") {
                    return ref;
                }
            } catch (e) {
                console.warn("unable to eval('" + name + "'):", e);
            }
            return null;
        },

        applyOptions: function(options) {
            this.parseOptions(options, null, ATTRIBUTES.map);
            this._applyParsedOptions(options);
        },

        setCenterZoom: function(location, zoom) {
            var coord = this.locationCoordinate(location).zoomTo(zoom);
            if (this.coordinate.zoom != coord.zoom || this.coordinate.row != coord.row || this.coordinate.column != coord.column) {
                this.coordinate = coord;
                this.draw();
                this.dispatchCallback('centered', [location, zoom]);
            } else {
                return this;
            }
        },

        panBy: function(dx, dy) {
            if (dx != 0 && dy != 0) {
                return MM.Map.prototype.panBy.call(this, dx, dy);
            } else {
                return this;
            }
        },

        zoomBy: function(zoomOffset) {
            if (zoomOffset != 0) {
                return MM.Map.prototype.zoomBy.call(this, zoomOffset);
            } else {
                return this;
            }
        },

        // our setProvider() accepts a string lookup
        setProvider: function(provider) {
            if (typeof provider === "string") {
                provider = PARSE.provider(provider);
            }
            return MM.Map.prototype.setProviderAt.call(this, 0, provider);
        },

        _applyParsedOptions: function(options) {
            if (options.provider) {
                // console.log("  * base provider:", options.provider);
                var baseLayer = this.layers[0],
                    provider = (typeof options.provider === "string")
                        ? PARSE.provider(options.provider)
                        : options.provider;
                if (provider) {
                    baseLayer.setProvider(provider);
                    this.parent.insertBefore(baseLayer.parent, this.parent.firstChild);
                    // XXX: force the base map layer to the bottom
                    baseLayer.parent.style.zIndex = 0;
                }
            }

            // and kick things off by setting the extent, center and zoom
            if (options.extent) {
                this.setExtent(options.extent);
            } else if (options.center) {
                if (isNaN(options.zoom)) {
                    this.setCenter(options.center);
                } else {
                    this.setCenterZoom(options.center, options.zoom);
                }
            } else if (!isNaN(options.zoom)) {
                this.setZoom(options.zoom);
            }
        },

        /**
         * HTMAPL doesn't know how to load files natively. For now we rely on
         * jQuery.ajax() and fill in support if it's available; otherwise, we throw
         * an exception.
         */
        ajax: function(url, options, success) {
            throw "Not implemented yet; include jQuery for remote file loading via jQuery.ajax()";
        },

        /**
         * DOM data getter. This is monkey patched if jQuery is present.
         */
        getData: function(element, key) {
            if (element.hasOwnProperty("dataset")) {
                key = key.toLowerCase();
                // console.log("looking for data:", [key], "in", element.dataset);
                return element.dataset[key] || element.getAttribute("data-" + key);
            } else {
                return element.getAttribute("data-" + key);
            }
        },

        // XXX: not used anywhere yet
        setData: function(element, key, value) {
            if (!element.hasOwnProperty("dataset")) {
                element.dataset = {};
            }
            element.dataset[key] = value;
        },

        parseOptions: function(options, element, parsers) {
            // console.log("parsing:", element, "into:", options, "with:", parsers);
            for (var key in parsers) {
                var value = element ? this.getData(element, key) : null;
                // console.log("option:", key, [value], typeof value);

                // allow for options to be set to 'false' 
                if (typeof value === "undefined") {
                    value = options[key];
                    // console.log("  default:", [value], typeof value);
                }

                // console.log(" +", key, "=", value);
                // if it's a string, parse it
                if (typeof value === "string" && parsers[key] !== String) {
                    options[key] = PARSE[parsers[key]].call(element, value);
                    // console.log("  + parsed:", [options[key]], typeof options[key]);
                // if it's not undefined, assign it
                } else if (typeof value !== "undefined") {
                    options[key] = value;
                    // console.log("  + passthru:", [options[key]], typeof options[key]);
                } else {
                    // console.info("invalid value for", key, ":", value);
                }
            }
        },

        getChildren: function(element, filter) {
            var children = element.childNodes,
                len = children.length,
                matched = [];

            // TODO: just use Sizzle?
            switch (typeof filter) {
                case "string":
                    if (filter.length > 1 && filter.charAt(0) === ".") {
                        var className = filter.substr(1),
                            pattern = new RegExp("\\b" + className + "\\b");
                        filter = function(child) {
                            return child.className && child.className.match(pattern);
                        };
                    } else {
                        // TODO: some other filter here? filter by selector?
                        console.warn("ignoring filter:", filter);
                        filter = null;
                    }
                    break;
                case "function":
                    // legit
                    break;
                default:
                    console.warn("invalid filter:", filter);
                    filter = null;
                    break;
            }

            for (var i = 0; i < len; i++) {
                var child = children[i];
                if (!filter || filter.call(this, child)) {
                    matched.push(child);
                }
            }
            return matched;
        }

    };

    // an HTMAPL.Map is an MM.Map, but better
    MM.extend(HTMAPL.Map, MM.Map);

    /**
     * Static utility functions
     */

    /**
     * HTMAPL.makeMap() takes a DOM node reference and a hash of default
     * options, and returns a new HTMAPL.Map instance, which extends
     * ModestMaps' Map.
     */
    HTMAPL.makeMap = function(element, defaults) {
        return new HTMAPL.Map(element, defaults);
    };

    /**
     * HTMAPL.makeMaps() iterates over a list of DOM nodes and returns a
     * corresponding array of HTMAPL.Map instances. This is kind of like:
     *
     * var nodes = document.querySelectorAll("div.map");
     * var options = { ... };
     * var maps = Array.prototype.slice.call(nodes).map(function(node) {
     *     return new HTMAPL.makeMap(node, options);
     * });
     */
    HTMAPL.makeMaps = function(elements, defaults) {
        var maps = [],
            len = elements.length;
        for (var i = 0; i < len; i++) {
            maps.push(new HTMAPL.Map(elements[i], defaults));
        }
        return maps;
    };

    /**
     * Utility functions (not needed in the global scope)
     */

    /**
     * extend() updates the properties of the object provided as its first
     * argument with the proprties of one or more other arguments. E.g.:
     *
     * var a = {foo: 1};
     * extend(a, {foo: 2, bar: 1});
     * // a.foo === 2, a.bar === 1
     */
    function extend(dest, sources) {
        var argc = arguments.length - 1;
        for (var i = 1; i <= argc; i++) {
            var source = arguments[i];
            if (!source) continue;
            for (var p in source) {
                dest[p] = source[p];
            }
        }
    }

	/**
	 * Parsing Functions
	 *
	 * The following functions are used to parse meaningful values from strings,
	 * and should return null if the provided strings don't match a predefined
	 * format.
	 */
    var PARSE = HTMAPL.parse = {};

    /**
     * Parse a query string, with or without the leading "?", and with an
     * optional parameter delimiter (the default is "&"). Returns a hash of
     * string key/value pairs.
     */
    PARSE.queryString = function(str, delim) {
        // chop off the leading ?
        if (str.charAt(0) == "?") str = str.substr(1);
        var parsed = {},
            parts = str.split(delim || "&"),
            len = parts.length;
        for (var i = 0; i < len; i++) {
            var bits = parts[i].split("=", 2);
            parsed[bits[0]] = decodeURIComponent(bits[1]);
        }
        return parsed;
    };

	/**
	 * Parse a {lat,lon} object from a string: "lat,lon", or return null if the
	 * string does not contain a single comma.
	 */
 	PARSE.latLon = function(str) {
		if (typeof str === "string" && str.indexOf(",") > -1) {
			var parts = str.split(/\s*,\s*/),
                lat = parseFloat(parts[0]),
                lon = parseFloat(parts[1]);
			return {lon: lon, lat: lat};
		}
		return null;
	};

	/**
	 * Parse an {x,y} object from a string: "x,x", or return null if the string
	 * does not contain a single comma.
	 */
 	PARSE.xy = function(str) {
		if (typeof str === "string" && str.indexOf(",") > -1) {
			var parts = str.split(/\s*,\s*/),
                x = parseInt(parts[0]),
                y = parseInt(parts[1]);
			return {x: x, y: y};
		}
		return null;
	};

	/**
	 * Parse an extent array [{lat,lon},{lat,lon}] from a string:
	 * "lat1,lon1,lat2,lon2", or return null if the string does not contain a
	 * 4 comma-separated numbers.
	 */
 	PARSE.extent = function(str) {
		if (typeof str === "string" && str.indexOf(",") > -1) {
			var parts = str.split(/\s*,\s*/);
			if (parts.length == 4) {
				var lat1 = parseFloat(parts[0]),
                    lon1 = parseFloat(parts[1]),
                    lat2 = parseFloat(parts[2]),
                    lon2 = parseFloat(parts[3]);
                return [{lon: Math.min(lon1, lon2),
                         lat: Math.max(lat1, lat2)},
                        {lon: Math.max(lon1, lon2),
                         lat: Math.min(lat1, lat2)}];
			}
		}
		return null;
	};

	/**
	 * Parse an integer from a string using parseInt(), or return null if the
	 * resulting value is NaN.
	 */
	PARSE.integer = function(str) {
		var i = parseInt(str);
		return isNaN(i) ? null : i;
	};

	/**
	 * Parse a float from a string using parseFloat(), or return null if the
	 * resulting value is NaN.
	 */
	PARSE["float"] = function(str) {
		var i = parseFloat(str);
		return isNaN(i) ? null : i;
	};

	/**
	 * Parse a string as a boolean "true" or "false", otherwise null.
	 */
	PARSE["boolean"] = function(str) {
		return (str === "true") ? true : (str === "false") ? false : null;
	};

	/**
	 * Parse a string as an array of at least two comma-separated strings, or
	 * null if it does not contain at least one comma.
	 */
	PARSE["array"] = function(str) {
		return (typeof str === "string" && str.indexOf(",") > -1) ? str.split(/\s*,\s*/) : null;
	};

	/**
	 * Parse a string as an array of integers.
	 */
	PARSE["numarray"] = function(str) {
        var a = PARSE.array(str);
        if (a) {
            var n = a.length;
            for (var i = 0; i < n; i++) {
                a[i] = Number(a[i]);
                if (isNaN(a[i])) {
                    return null;
                }
            }
        }
        return a;
	};

    var PROVIDERS = HTMAPL.providers = {};
    /**
     * Register a named map tile provider. Basic usage:
     *
     * HTMAPL.registerProvider("name", new com.modestmaps.MapProvider(...));
     *
     * You can also register tile provider "generator" prefixes with a colon
     * between the prefix and the generator argument(s). E.g.:
     *
     * HTMAPL.registerProvider("prefix", function(layer) {
     *     var url = "path/to/" + layer + "/{Z}/{X}/{Y}.png";
     *     return new com.modestmaps.TemplatedMapProvider(url);
     * });
     */
    PROVIDERS.register = function(name, provider) {
        if (typeof provider === "undefined") {
            PROVIDERS.unregister(name);
        } else {
            PROVIDERS[name] = provider;
        }
    };

    PROVIDERS.unregister = function(name) {
        delete PROVIDERS[name];
    };

    /**
     * Get a named provider
     */
    PARSE.provider = function(str) {
        if (str in PROVIDERS) {
            return (typeof PROVIDERS[str] === "function")
                ? PROVIDERS[str].call(null)
                : PROVIDERS[str];
        } else if (str.indexOf(":") > -1) {
            var parts = str.split(":"),
                prefix = parts.shift();
            if (prefix in PROVIDERS) {
                return PROVIDERS[prefix].apply(null, parts);
            }
        } else {
            return url
                ? new MM.TemplatedMapProvider(url)
                : NULL_PROVIDER;
        }
    };

    PARSE.centerZoom = function(zoomLatLon) {
        if (zoomLatLon.charAt(0) === "#") zoomLatLon = zoomLatLon.substr(1);
        var parts = zoomLatLon.split("/"),
            zoom = parseInt(parts[0]),
            lat = parseFloat(parts[1]),
            lon = parseFloat(parts[2]);
        if (isNaN(zoom) || isNaN(lat) || isNaN(lon)) {
            return null;
        } else {
            return {
                center: {lat: lat, lon: lon},
                zoom: zoom
            };
        }
    };

    /**
     * Built-in providers
     */
    var NULL_PROVIDER = new MM.MapProvider(function(c) { return null; });
    PROVIDERS.register("none",  NULL_PROVIDER);
    PROVIDERS.register("toner", new MM.TemplatedMapProvider("http://spaceclaw.stamen.com/toner/{Z}/{X}/{Y}.png"));
    PROVIDERS.register("stamen:toner", new MM.TemplatedMapProvider("http://spaceclaw.stamen.com/toner/{Z}/{X}/{Y}.png"));

    /**
     * Cloudmade style map provider generator.
     */
    PROVIDERS["bing"] = (function() {

        function makeQueryString(params) {
            var parts = [];
            for (var key in params) {
                var value = params[key];
                if (typeof value === "string" && value.length) {
                    parts.push(key, "=", encodeURIComponent(params[key]), "&");
                }
            }
            parts.pop();
            return parts.join("");
        }

        var bing = function(queryString) {
            var params = {};
            extend(params, bing.defaults);
            if (arguments.length > 0 && queryString) {
                try {
                    var parsed = PARSE.queryString(queryString);
                    extend(params, parsed);
                } catch (e) {
                    throw 'Unable to parse query string "' + queryString + '": ' + e;
                }
            }
            queryString = makeQueryString(params);
            return new MM.TemplatedMapProvider("http://ecn.t{S}.tiles.virtualearth.net/tiles/r{Q}?" + queryString, bing.subdomains);
        }
        bing.subdomains = [0, 1, 2, 3, 4, 5, 6, 7];
        bing.defaults = PARSE.queryString("g=689&mkt=en-us&lbl=l1&stl=h&shading=hill");
        return bing;
    })();

    /**
     * Cloudmade style map provider generator.
     */
    PROVIDERS["cloudmade"] = (function() {
        var aliases = {
            "fresh":    997,
            "paledawn": 998,
            "midnight": 999
        };

        var cloudmade = function(styleId) {
            if (styleId in aliases) {
                styleId = aliases[styleId];
            }
            return new MM.TemplatedMapProvider([
                "http://{S}tile.cloudmade.com",
                cloudmade.key,
                styleId,
                "256/{Z}/{X}/{Y}.png"
            ].join("/"), cloudmade.domains);
        };

        // FIXME: use another key? require the user to set this?
        cloudmade.key = "1a1b06b230af4efdbb989ea99e9841af";
        cloudmade.domains = ["a.", "b.", "c.", ""];
        // e.g.:
        // HTMAPL.providers.cloudmade.registerAlias("fresh", 997);
        cloudmade.registerAlias = function(alias, styleId) {
            aliases[alias] = styleId;
        };

        return cloudmade;
    })();

    /**
     * Mapbox provider generator
     */
    PROVIDERS["mapbox"] = (function() {
        var mapbox = function(layer) {
            // if we have multiple arguments, join them into one: "user.layer"
            if (arguments.length > 1) {
                layer = Array.prototype.join.call(arguments, ".");
            }
            return new MM.TemplatedMapProvider(
                "http://{S}.tiles.mapbox.com/v2/" + layer + "/{Z}/{X}/{Y}.png",
                mapbox.domains);
        };
        mapbox.domains = ["a", "b", "c"];
        return mapbox;
    })();

    /**
     * Acetate layer generator
     */
    PROVIDERS["acetate"] = function(layer) {
        if (!layer) layer = "acetate";
        else if (layer.indexOf("acetate") != 0) layer = "acetate-" + layer;
        return new MM.TemplatedMapProvider("http://acetate.geoiq.com/tiles/" + layer + "/{Z}/{X}/{Y}.png");
    };

    // jQuery-specific stuff
    if (typeof jQuery !== "undefined") {

        var $ = jQuery;

        // use jQuery.ajax();
        HTMAPL.Map.prototype.ajax = function(url, options, success) {
            return $.ajax(url, options).done(success);
        };

        /**
         * Use jQuery.data() so that you can set data via the same interface:
         *
         * $("div.map").data("provider", "toner").htmapl();
         */
        HTMAPL.Map.prototype.getData = function(element, key) {
            return $(element).data(key);
        };

        HTMAPL.Map.prototype.getChildren = function(element, filter) {
            return $(element).children(filter);
        };

        /**
         * Monkey patch Map::getBuildMarker() if jQuery templates are
         * available. This modifies the prototype method to look for a named
         * template
         */
        if (typeof $.fn.tmpl === "function") {
            var oldBuildMarker = HTMAPL.Map.prototype.getBuildMarker;
            HTMAPL.Map.prototype.getBuildMarker = function(name) {
                var existing = oldBuildMarker(name);
                if (existing) {
                    return exiting;
                } else {
                    // check to see if the provided name is a selector
                    var target = $(name);
                    // if there's a matching element, use that as the template
                    // and return a function that uses that template in a closure
                    if (target.length == 1) {
                        var template = target.template();
                        return function(feature) {
                            return $.tmpl(template, feature).get(0);
                        };
                    // otherwise, return a function that passes the name in as
                    // the template identifier
                    } else {
                        return function(feature) {
                            return $.tmp(name, feature).get(0);
                        };
                    }
                }
            };
        }

        // keep a reference around to the plugin object for exporting useful functions
        $.fn.htmapl = function(options, argn) {
            var args = Array.prototype.slice.call(arguments, 1);
            return this.each(function() {
                var $this = $(this),
                    map = $(this).data("map");
                if (map) {
                    if (typeof options === "string") {
                        if (typeof map[options] === "function") {
                            var method = options;
                            // console.log("calling map." + method, "with:", args);
                            map[method].apply(map, args);
                        } else {
                            map[options] = argn;
                        }
                    } else if (typeof options === "object") {
                        map.applyOptions(options);
                    }
                } else {
                    try {
                        map = HTMAPL.makeMap(this, options);
                        var layers = map.layers,
                            len = layers.length;
                        for (var i = 0; i < len; i++) {
                            $(layers[i].parent).data("layer", layers[i]);
                        }
                        $this.data("map", map);

                        map.addCallback("panned", function(_, panOffset) {
                            $this.trigger("map.panned", {center: map.getCenter(), offset: panOffset});
                        });

                        map.addCallback("zoomed", function(_, zoomDelta) {
                            $this.trigger("map.zoomed", {zoom: map.getZoom(), delta: zoomDelta});
                        });

                        map.addCallback("centered", function() {
                            $this.trigger("map.centered", {center: map.getCenter()});
                        });

                        map.addCallback("extentset", function() {
                            $this.trigger("map.extentset", {extent: map.getExtent()});
                        });

                        map.addCallback("resized", function() {
                            $this.trigger("map.resized", {size: map.dimensions});
                        });

                    } catch (e) {
                        console.error("unable to makeMap(): ", e.message);
                    }
                }
            });
        };

        $.fn.center = function() {
            if (arguments.length == 0) {
                return this.data("map").getCenter();
            } else {
                var center, zoom;
                if (arguments.length == 1) {
                    if (typeof arguments[0] === "object") {
                        center = arguments[0];
                    } else if (typeof arguments[0] === "string") {
                        center = PARSE.latLon(arguments[0]);
                    }
                } else if (arguments.length == 2) {
                    if (typeof arguments[0] === "object") {
                        center = arguments[0];
                        zoom = Number(arguments[1]);
                    } else {
                        center = {lat: Number(arguments[0]), lon: Number(arguments[1])};
                    }
                } else if (arguments.length == 3) {
                    center = {lat: Number(arguments[0]), lon: Number(arguments[1])};
                    zoom = Number(arguments[2]);
                }
                if (!center) {
                    return this;
                }
                return isNaN(zoom)
                    ? this.each(function() {
                        $(this).data("map").setCenter(center);
                    })
                    : this.each(function() {
                        $(this).data("map").setCenterZoom(center, zoom);
                    });
            }
        };

        $.fn.zoom = function(zoom) {
            if (arguments.length == 0) {
                return this.data("map").getZoom();
            } else {
                if (typeof zoom === "string") {
                    zoom = parseInt(zoom);
                }
                return this.each(function() {
                    $(this).data("map").setZoom(zoom);
                });
            }
        };

        $.fn.extent = function(extent) {
            if (arguments.length == 0) {
                return this.data("map").getExtent();
            } else {
                if (typeof extent === "string") {
                    extent = PARSE.extent(extent);
                }
                return this.each(function() {
                    $(this).data("map").setExtent(extent);
                });
            }
        };

        $.fn.centerZoom = function(lat, lon, zoom) {
            var center;
            if (arguments.length == 2) {
                if (typeof lat === "object") {
                    center = lat;
                } else if (typeof lat === "string") {
                    center = PARSE.latLon(lat);
                }
                zoom = lon;
            } else {
                center = {lat: Number(lat), lon: Number(lon)};
            }
            return this.each(function() {
                $(this).data("map").setCenterZoom(center, zoom);
            });
        };

        // TODO: no getProvider()?
        $.fn.provider = function(provider) {
            return this.each(function() {
                $(this).data("map").setProvider(provider);
            });
        };

        // automatically map-ulate anything with data-htmapl="true"
        $(function() {
            $("*[data-htmapl=true]").htmapl();
        });

    }

})();
(function(MM) {

    /**
     * The MarkerLayer doesn't do any tile stuff, so it doesn't need to
     * inherit from MM.Layer. The constructor takes only an optional parent
     * element.
     *
     * Usage:
     *
     * // create the map with some constructor parameters
     * var map = new MM.Map(...);
     * // create a new MarkerLayer instance and add it to the map
     * var layer = new MM.MarkerLayer();
     * map.addLayer(layer);
     * // create a marker element
     * var marker = document.createElement("a");
     * marker.innerHTML = "Stamen";
     * // add it to the layer at the specified geographic location
     * layer.addMarker(marker, new MM.Location(37.764, -122.419));
     * // center the map on the marker's location
     * map.setCenterZoom(marker.location, 13);
     *
     */
    MM.MarkerLayer = function(parent) {
        this.parent = parent || document.createElement('div');
        this.parent.style.cssText = 'position: absolute; top: 0px; left: 0px; width: 100%; height: 100%; margin: 0; padding: 0; z-index: 0';
        this.markers = [];
        this.resetPosition();
    };

    MM.MarkerLayer.prototype = {
        // a list of our markers
        markers: null,
        // the absolute position of the parent element
        position: null,

        // the last coordinate we saw on the map
        lastCoord: null,
        draw: function() {
            // these are our previous and next map center coordinates
            var prev = this.lastCoord,
                next = this.map.pointCoordinate({x: 0, y: 0});
            // if we've recorded the map's previous center...
            if (prev) {
                // if the zoom hasn't changed, find the delta in screen
                // coordinates and pan the parent element
                if (prev.zoom == next.zoom) {
                    var p1 = this.map.coordinatePoint(prev),
                        p2 = this.map.coordinatePoint(next),
                        dx = p1.x - p2.x,
                        dy = p1.y - p2.y;
                    // console.log("panned:", [dx, dy]);
                    this.onPanned(dx, dy);
                // otherwise, reposition all the markers
                } else {
                    this.onZoomed();
                }
            // otherwise, reposition all the markers
            } else {
                this.onZoomed();
            }
            // remember the previous center
            this.lastCoord = next.copy();
        },

        // when zoomed, reset the position and reposition all markers
        onZoomed: function() {
            this.resetPosition();
            this.repositionAllMarkers();
        },

        // when panned, offset the position by the provided screen coordinate x
        // and y values
        onPanned: function(dx, dy) {
            this.position.x += dx;
            this.position.y += dy;
            this.parent.style.left = ~~(this.position.x + .5) + "px";
            this.parent.style.top = ~~(this.position.y + .5) + "px";
        },

        // remove all markers
        removeAllMarkers: function() {
            while (this.markers.length > 0) {
                this.removeMarker(this.markers[0]);
            }
        },

        /**
         * Coerce the provided value into a Location instance. The following
         * values are supported:
         *
         * 1. MM.Location instances
         * 2. Object literals with numeric "lat" and "lon" properties
         * 3. A string in the form "lat,lon"
         * 4. GeoJSON objects with "Point" geometries
         *
         * This function throws an exception on error.
         */
        coerceLocation: function(feature) {
            switch (typeof feature) {
                case "string":
                    // "lat,lon" string
                    return MM.Location.fromString(feature);

                case "object":
                    // GeoJSON
                    if (typeof feature.geometry === "object") {
                        var geom = feature.geometry;
                        switch (geom.type) {
                            // Point geometries => MM.Location
                            case "Point":
                                // coerce the lat and lon values, just in case
                                var lon = Number(geom.coordinates[0]),
                                    lat = Number(geom.coordinates[1]);
                                return new MM.Location(lat, lon);
                        }
                        throw 'Unable to get the location of GeoJSON "' + geom.type + '" geometry!';
                    } else if (feature instanceof MM.Location ||
                        (typeof feature.lat !== "undefined" && typeof feature.lon !== "undefined")) {
                        return feature;
                    } else {
                        throw 'Unknown location object; no "lat" and "lon" properties found!';
                    }
                    break;

                case "undefined":
                    throw 'Location "undefined"';
            }
        },

        /**
         * Add an HTML element as a marker, located at the position of the
         * provided GeoJSON feature, Location instance (or {lat,lon} object
         * literal), or "lat,lon" string.
         */
        addMarker: function(marker, feature) {
            if (!marker || !feature) {
                return null;
            }
            // convert the feature to a Location instance
            marker.location = this.coerceLocation(feature);
            // position: absolute
            marker.style.position = "absolute";
            if (this.map) {
                // update the marker's position
                this.repositionMarker(marker);
            }
            // append it to the DOM
            this.parent.appendChild(marker);
            // add it to the list
            this.markers.push(marker);
            return marker;
        },

        /**
         * Remove the element marker from the layer and the DOM.
         */
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

        // reset the absolute position of the layer's parent element
        resetPosition: function() {
            this.position = new MM.Point(0, 0);
            this.parent.style.left = this.parent.style.top = "0px";
        },

        // reposition a single marker element
        repositionMarker: function(marker) {
            if (!marker.coord) {
                marker.coord = this.map.locationCoordinate(marker.location);
            }
            var pos = this.map.coordinatePoint(marker.coord);
            // offset by the layer parent position if x or y is non-zero
            if (this.position.x || this.position.y) {
                pos.x -= this.position.x;
                pos.y -= this.position.y;
            }
            marker.style.left = ~~(pos.x + .5) + "px";
            marker.style.top = ~~(pos.y + .5) + "px";
        },

        // Reposition al markers
        repositionAllMarkers: function() {
            var len = this.markers.length;
            for (var i = 0; i < len; i++) {
                this.repositionMarker(this.markers[i]);
            }
        }
    };

    // Array.indexOf polyfill courtesy of Mozilla MDN:
    // https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/Array/indexOf
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

})(MM);
(function(MM) {

    MM.MouseMoveZoomHandler = function(map, zooms, factors) {
        this.onMouseMove = MM.bind(this.onMouseMove, this);
        if (map) {
            this.init(map);
        }
        if (typeof zooms === "object") {
            if (zooms[2] > zooms[0]) {
                this.innerZoom = zooms[2];
                this.midZoom = zooms[1];
                this.outerZoom = zooms[0];
            } else {
                this.innerZoom = zooms[0];
                this.midZoom = zooms[1];
                this.outerZoom = zooms[2];
            }
        }
        if (typeof factors === "object") {
            this.innerZoomFactor = Math.max(factors[0], factors[1]);
            this.outerZoomFactor = Math.min(factors[0], factors[1]);
        }
    };

    MM.MouseMoveZoomHandler.prototype = {
        // outer zoom
        outerZoom: 3,
        // mouse distance from center (0-1) at which the outer zoom applies
        outerZoomFactor: .6,
        // mid-level zoom (applies at outerZoomFactor < distance < innerZoomFactor)
        midZoom: 9,
        // inner zoom
        innerZoom: 13,
        // mouse distance from center (0-1) at which the inner zoom applies
        innerZoomFactor: .2,

        init: function(map) {
            this.map = map;
            MM.addEvent(this.map.parent, "mousemove", this.onMouseMove);
        },

        remove: function() {
            MM.removeEvent(this.map.parent, "mousemove", this.onMouseMove);
            this.map = null;
        },

        onMouseMove: function(e) {
            var mouse = MM.getMousePoint(e, this.map),
                size = this.map.dimensions,
                // center x and y
                cx = size.x / 2,
                cy = size.y / 2,
                // mouse distance from center in x and y dims
                dx = Math.abs(cx - mouse.x) / cx,
                dy = Math.abs(cy - mouse.y) / cy,
                // normalized distance is the max of dx and dy
                f = Math.max(dx, dy),
                // default zoom is mid-level
                z = this.midZoom;
            if (f <= this.innerZoomFactor) {
                z = this.innerZoom;
            } else if (f >= this.outerZoomFactor) {
                z = this.outerZoom;
            }
            this.map.setCenterZoom(this.map.getCenter(), z);
        }
    };

})(MM);
