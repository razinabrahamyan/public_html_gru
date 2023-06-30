! function(t, e) {
    "object" == typeof exports && "object" == typeof module ? module.exports = e() :
     "function" == typeof define && define.amd ? define([], e) :
      "object" == typeof exports ?
       exports.Strike = e() : t.Strike = e()
}(window, function() {
    return function(t) {
        var e = {};

        function n(r) {
            if (e[r]) return e[r].exports;
            var o = e[r] = {
                i: r,
                l: !1,
                exports: {}
            };
            return t[r].call(o.exports, o, o.exports, n), o.l = !0, o.exports
        }
        return n.m = t, n.c = e, n.d = function(t, e, r) {
            n.o(t, e) || Object.defineProperty(t, e, {
                enumerable: !0,
                get: r
            })
        }, n.r = function(t) {
            "undefined" != typeof Symbol && Symbol.toStringTag && Object.defineProperty(t, Symbol.toStringTag, {
                value: "Module"
            }), Object.defineProperty(t, "__esModule", {
                value: !0
            })
        }, n.t = function(t, e) {
            if (1 & e && (t = n(t)), 8 & e) return t;
            if (4 & e && "object" == typeof t && t && t.__esModule) return t;
            var r = Object.create(null);
            if (n.r(r), Object.defineProperty(r, "default", {
                    enumerable: !0,
                    value: t
                }), 2 & e && "string" != typeof t)
                for (var o in t) n.d(r, o, function(e) {
                    return t[e]
                }.bind(null, o));
            return r
        }, n.n = function(t) {
            var e = t && t.__esModule ? function() {
                return t.default
            } : function() {
                return t
            };
            return n.d(e, "a", e), e
        }, n.o = function(t, e) {
            return Object.prototype.hasOwnProperty.call(t, e)
        }, n.p = "/", n(n.s = 0)
    }([function(t, e, n) {
        function r(t, e) {
            for (var n = 0; n < e.length; n++) {
                var r = e[n];
                r.enumerable = r.enumerable || !1, r.configurable = !0, "value" in r && (r.writable = !0), Object.defineProperty(t, r.key, r)
            }
        }

        function o(t, e, n) {
            return e && r(t.prototype, e), n && r(t, n), t
        }
        n(1).toString();
        var i = function() {
            function t(e) {
                var n = e.api;
                ! function(t, e) {
                    if (!(t instanceof e)) throw new TypeError("Cannot call a class as a function")
                }(this, t), this.api = n, this.button = null, this.tag = "S", this.iconClasses = {
                    base: this.api.styles.inlineToolButton,
                    active: this.api.styles.inlineToolButtonActive
                }
            }
            return o(t, null, [{
                key: "CSS",
                get: function() {
                    return "cdx-strike"
                }
            }]), o(t, [{
                key: "render",
                value: function() {
                    return this.button = document.createElement("button"), this.button.type = "button", this.button.classList.add(this.iconClasses.base), this.button.innerHTML = this.toolboxIcon, this.button
                }
            }, {
                key: "surround",
                value: function(e) {
                    if (e) {
                        var n = this.api.selection.findParentTag(this.tag, t.CSS);
                        n ? this.unwrap(n) : this.wrap(e)
                    }
                }
            }, {
                key: "wrap",
                value: function(e) {
                    var n = document.createElement(this.tag);
                    n.classList.add(t.CSS), n.appendChild(e.extractContents()), e.insertNode(n), this.api.selection.expandToTag(n)
                }
            }, {
                key: "unwrap",
                value: function(t) {
                    this.api.selection.expandToTag(t);
                    var e = window.getSelection(),
                        n = e.getRangeAt(0),
                        r = n.extractContents();
                    t.parentNode.removeChild(t), n.insertNode(r), e.removeAllRanges(), e.addRange(n)
                }
            }, {
                key: "checkState",
                value: function() {
                    var e = this.api.selection.findParentTag(this.tag, t.CSS);
                    this.button.classList.toggle(this.iconClasses.active, !!e)
                }
            }, {
                key: "toolboxIcon",
                get: function() {
                    return n(6).default
                }
            }], [{
                key: "isInline",
                get: function() {
                    return !0
                }
            }, {
                key: "sanitize",
                get: function() {
                    return {
                        s: {
                            class: t.CSS
                        }
                    }
                }
            }]), t
        }();
        t.exports = i
    }, function(t, e, n) {
        var r = n(2);
        "string" == typeof r && (r = [
            [t.i, r, ""]
        ]);
        var o = {
            hmr: !0,
            transform: void 0,
            insertInto: void 0
        };
        n(4)(r, o);
        r.locals && (t.exports = r.locals)
    }, function(t, e, n) {
        (t.exports = n(3)(!1)).push([t.i, ".cdx-strike {\n  font-family: inherit;\n  font-weight: 500;\n}\n", ""])
    }, function(t, e) {
        t.exports = function(t) {
            var e = [];
            return e.toString = function() {
                return this.map(function(e) {
                    var n = function(t, e) {
                        var n = t[1] || "",
                            r = t[3];
                        if (!r) return n;
                        if (e && "function" == typeof btoa) {
                            var o = (a = r, "/*# sourceMappingURL=data:application/json;charset=utf-8;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(a)))) + " */"),
                                i = r.sources.map(function(t) {
                                    return "/*# sourceURL=" + r.sourceRoot + t + " */"
                                });
                            return [n].concat(i).concat([o]).join("\n")
                        }
                        var a;
                        return [n].join("\n")
                    }(e, t);
                    return e[2] ? "@media " + e[2] + "{" + n + "}" : n
                }).join("")
            }, e.i = function(t, n) {
                "string" == typeof t && (t = [
                    [null, t, ""]
                ]);
                for (var r = {}, o = 0; o < this.length; o++) {
                    var i = this[o][0];
                    "number" == typeof i && (r[i] = !0)
                }
                for (o = 0; o < t.length; o++) {
                    var a = t[o];
                    "number" == typeof a[0] && r[a[0]] || (n && !a[2] ? a[2] = n : n && (a[2] = "(" + a[2] + ") and (" + n + ")"), e.push(a))
                }
            }, e
        }
    }, function(t, e, n) {
        var r, o, i = {},
            a = (r = function() {
                return window && document && document.all && !window.atob
            }, function() {
                return void 0 === o && (o = r.apply(this, arguments)), o
            }),
            s = function(t) {
                var e = {};
                return function(t) {
                    if ("function" == typeof t) return t();
                    if (void 0 === e[t]) {
                        var n = function(t) {
                            return document.querySelector(t)
                        }.call(this, t);
                        if (window.HTMLIFrameElement && n instanceof window.HTMLIFrameElement) try {
                            n = n.contentDocument.head
                        } catch (t) {
                            n = null
                        }
                        e[t] = n
                    }
                    return e[t]
                }
            }(),
            u = null,
            c = 0,
            f = [],
            l = n(5);

        function p(t, e) {
            for (var n = 0; n < t.length; n++) {
                var r = t[n],
                    o = i[r.id];
                if (o) {
                    o.refs++;
                    for (var a = 0; a < o.parts.length; a++) o.parts[a](r.parts[a]);
                    for (; a < r.parts.length; a++) o.parts.push(g(r.parts[a], e))
                } else {
                    var s = [];
                    for (a = 0; a < r.parts.length; a++) s.push(g(r.parts[a], e));
                    i[r.id] = {
                        id: r.id,
                        refs: 1,
                        parts: s
                    }
                }
            }
        }

        function d(t, e) {
            for (var n = [], r = {}, o = 0; o < t.length; o++) {
                var i = t[o],
                    a = e.base ? i[0] + e.base : i[0],
                    s = {
                        css: i[1],
                        media: i[2],
                        sourceMap: i[3]
                    };
                r[a] ? r[a].parts.push(s) : n.push(r[a] = {
                    id: a,
                    parts: [s]
                })
            }
            return n
        }

        function h(t, e) {
            var n = s(t.insertInto);
            if (!n) throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
            var r = f[f.length - 1];
            if ("top" === t.insertAt) r ? r.nextSibling ? n.insertBefore(e, r.nextSibling) : n.appendChild(e) : n.insertBefore(e, n.firstChild), f.push(e);
            else if ("bottom" === t.insertAt) n.appendChild(e);
            else {
                if ("object" != typeof t.insertAt || !t.insertAt.before) throw new Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
                var o = s(t.insertInto + " " + t.insertAt.before);
                n.insertBefore(e, o)
            }
        }

        function v(t) {
            if (null === t.parentNode) return !1;
            t.parentNode.removeChild(t);
            var e = f.indexOf(t);
            e >= 0 && f.splice(e, 1)
        }

        function b(t) {
            var e = document.createElement("style");
            return void 0 === t.attrs.type && (t.attrs.type = "text/css"), y(e, t.attrs), h(t, e), e
        }

        function y(t, e) {
            Object.keys(e).forEach(function(n) {
                t.setAttribute(n, e[n])
            })
        }

        function g(t, e) {
            var n, r, o, i;
            if (e.transform && t.css) {
                if (!(i = e.transform(t.css))) return function() {};
                t.css = i
            }
            if (e.singleton) {
                var a = c++;
                n = u || (u = b(e)), r = x.bind(null, n, a, !1), o = x.bind(null, n, a, !0)
            } else t.sourceMap && "function" == typeof URL && "function" == typeof URL.createObjectURL && "function" == typeof URL.revokeObjectURL && "function" == typeof Blob && "function" == typeof btoa ? (n = function(t) {
                var e = document.createElement("link");
                return void 0 === t.attrs.type && (t.attrs.type = "text/css"), t.attrs.rel = "stylesheet", y(e, t.attrs), h(t, e), e
            }(e), r = function(t, e, n) {
                var r = n.css,
                    o = n.sourceMap,
                    i = void 0 === e.convertToAbsoluteUrls && o;
                (e.convertToAbsoluteUrls || i) && (r = l(r));
                o && (r += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(o)))) + " */");
                var a = new Blob([r], {
                        type: "text/css"
                    }),
                    s = t.href;
                t.href = URL.createObjectURL(a), s && URL.revokeObjectURL(s)
            }.bind(null, n, e), o = function() {
                v(n), n.href && URL.revokeObjectURL(n.href)
            }) : (n = b(e), r = function(t, e) {
                var n = e.css,
                    r = e.media;
                r && t.setAttribute("media", r);
                if (t.styleSheet) t.styleSheet.cssText = n;
                else {
                    for (; t.firstChild;) t.removeChild(t.firstChild);
                    t.appendChild(document.createTextNode(n))
                }
            }.bind(null, n), o = function() {
                v(n)
            });
            return r(t),
                function(e) {
                    if (e) {
                        if (e.css === t.css && e.media === t.media && e.sourceMap === t.sourceMap) return;
                        r(t = e)
                    } else o()
                }
        }
        t.exports = function(t, e) {
            if ("undefined" != typeof DEBUG && DEBUG && "object" != typeof document) throw new Error("The style-loader cannot be used in a non-browser environment");
            (e = e || {}).attrs = "object" == typeof e.attrs ? e.attrs : {}, e.singleton || "boolean" == typeof e.singleton || (e.singleton = a()), e.insertInto || (e.insertInto = "head"), e.insertAt || (e.insertAt = "bottom");
            var n = d(t, e);
            return p(n, e),
                function(t) {
                    for (var r = [], o = 0; o < n.length; o++) {
                        var a = n[o];
                        (s = i[a.id]).refs--, r.push(s)
                    }
                    t && p(d(t, e), e);
                    for (o = 0; o < r.length; o++) {
                        var s;
                        if (0 === (s = r[o]).refs) {
                            for (var u = 0; u < s.parts.length; u++) s.parts[u]();
                            delete i[s.id]
                        }
                    }
                }
        };
        var m, w = (m = [], function(t, e) {
            return m[t] = e, m.filter(Boolean).join("\n")
        });

        function x(t, e, n, r) {
            var o = n ? "" : r.css;
            if (t.styleSheet) t.styleSheet.cssText = w(e, o);
            else {
                var i = document.createTextNode(o),
                    a = t.childNodes;
                a[e] && t.removeChild(a[e]), a.length ? t.insertBefore(i, a[e]) : t.appendChild(i)
            }
        }
    }, function(t, e) {
        t.exports = function(t) {
            var e = "undefined" != typeof window && window.location;
            if (!e) throw new Error("fixUrls requires window.location");
            if (!t || "string" != typeof t) return t;
            var n = e.protocol + "//" + e.host,
                r = n + e.pathname.replace(/\/[^\/]*$/, "/");
            return t.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(t, e) {
                var o, i = e.trim().replace(/^"(.*)"$/, function(t, e) {
                    return e
                }).replace(/^'(.*)'$/, function(t, e) {
                    return e
                });
                return /^(#|data:|http:\/\/|https:\/\/|file:\/\/\/|\s*$)/i.test(i) ? t : (o = 0 === i.indexOf("//") ? i : 0 === i.indexOf("/") ? n + i : r + i.replace(/^\.\//, ""), "url(" + JSON.stringify(o) + ")")
            })
        }
    }, function(t, e, n) {
        "use strict";
        n.r(e),
        e.default = '<svg width="17" height="12" viewBox="1 -1 16 15" xmlns="http://www.w3.org/2000/svg">' + 
        '<path d="M 11.625 5.25 L 6.886719 5.25 L 4.84375 4.621094 C 4.363281 4.472656 4.0625 3.992188 4.136719 3.492188 C 4.210938 2.996094 4.640625 2.625 5.144531 2.625 L 6.710938 2.625 C 7.15625 2.625 7.558594 2.875 7.757812 3.269531 C 7.851562 3.457031 8.074219 3.53125 8.261719 3.4375 L 9.265625 2.9375 C 9.453125 2.84375 9.527344 2.617188 9.433594 2.433594 L 9.421875 2.410156 C 8.914062 1.390625 7.875 0.75 6.738281 0.75 L 5.144531 0.75 C 4.328125 0.75 3.546875 1.097656 3 1.703125 C 2.449219 2.308594 2.183594 3.117188 2.261719 3.929688 C 2.308594 4.417969 2.5 4.863281 2.773438 5.25 L 0.375 5.25 C 0.167969 5.25 0 5.417969 0 5.625 L 0 6.375 C 0 6.582031 0.167969 6.75 0.375 6.75 L 11.625 6.75 C 11.832031 6.75 12 6.582031 12 6.375 L 12 5.625 C 12 5.417969 11.832031 5.25 11.625 5.25 Z M 7.402344 7.5 C 7.695312 7.683594 7.875 8.007812 7.875 8.355469 C 7.875 8.917969 7.417969 9.375 6.855469 9.375 L 5.289062 9.375 C 4.84375 9.375 4.441406 9.125 4.242188 8.730469 C 4.148438 8.542969 3.925781 8.46875 3.738281 8.5625 L 2.734375 9.0625 C 2.546875 9.15625 2.472656 9.382812 2.566406 9.566406 L 2.578125 9.589844 C 3.085938 10.609375 4.125 11.25 5.261719 11.25 L 6.855469 11.25 C 7.671875 11.25 8.453125 10.902344 9 10.296875 C 9.550781 9.691406 9.816406 8.882812 9.738281 8.070312 C 9.71875 7.875 9.675781 7.683594 9.613281 7.5 Z M 7.402344 7.5 " id="a"/>'
        +'</svg>\n'
    }])
});