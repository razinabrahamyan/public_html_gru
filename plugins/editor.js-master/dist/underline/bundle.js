! function(t, e) {
    "object" == typeof exports && "object" == typeof module ? module.exports = e() :
     "function" == typeof define && define.amd ? define([], e) :
      "object" == typeof exports ?
       exports.Underline = e() : t.Underline = e()
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
                }(this, t), this.api = n, this.button = null, 
                    this.tag = "U", 
                    this.iconClasses = {
                    base: this.api.styles.inlineToolButton,
                    active: this.api.styles.inlineToolButtonActive
                }
            }
            return o(t, null, [{
                key: "CSS",
                get: function() {
                    return "underline"
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
                        u: {
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
        (t.exports = n(3)(!1)).push([t.i, ".underline {\n  font-family: inherit;\n  font-weight: 500;\n}\n", ""])
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
        e.default = '<svg width="17" height="12" viewBox="1 -1 16 15" xmlns="http://www.w3.org/2000/svg"><path d="M 1.214844 1.5 L 2.429688 1.5 L 2.429688 5.25 C 2.429688 7.316406 5.152344 9 8.5 9 C 11.847656 9 14.570312 7.316406 14.570312 5.25 L 14.570312 1.5 L 15.785156 1.5 C 16.121094 1.5 16.394531 1.332031 16.394531 1.125 L 16.394531 0.375 C 16.394531 0.167969 16.121094 0 15.785156 0 L 10.320312 0 C 9.984375 0 9.714844 0.167969 9.714844 0.375 L 9.714844 1.125 C 9.714844 1.332031 9.984375 1.5 10.320312 1.5 L 11.535156 1.5 L 11.535156 5.25 C 11.535156 6.285156 10.175781 7.125 8.5 7.125 C 6.824219 7.125 5.464844 6.285156 5.464844 5.25 L 5.464844 1.5 L 6.679688 1.5 C 7.015625 1.5 7.285156 1.332031 7.285156 1.125 L 7.285156 0.375 C 7.285156 0.167969 7.015625 0 6.679688 0 L 1.214844 0 C 0.878906 0 0.605469 0.167969 0.605469 0.375 L 0.605469 1.125 C 0.605469 1.332031 0.878906 1.5 1.214844 1.5 Z M 16.394531 10.5 L 0.605469 10.5 C 0.273438 10.5 0 10.667969 0 10.875 L 0 11.625 C 0 11.832031 0.273438 12 0.605469 12 L 16.394531 12 C 16.726562 12 17 11.832031 17 11.625 L 17 10.875 C 17 10.667969 16.726562 10.5 16.394531 10.5 Z M 16.394531 10.5 " id="a"/></svg>\n'
    }])
});