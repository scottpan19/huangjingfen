/**
 * H5 polyfill: 在 UniApp 框架初始化完成前，为 uni API 提供浏览器降级实现。
 *
 * 核心策略：
 *   - 用 Object.defineProperty 拦截 window.uni 的赋值，防止 UniApp 运行时
 *     用存根对象覆盖我们的实现。
 *   - 当 UniApp 将 stub 对象赋给 window.uni 时，把 stub 合并到我们的代理
 *     对象中，同时保留我们对"未实现"API 的真实降级实现。
 *   - 之后 UniApp 注册真正的 H5 实现（如 uni.request = xhrImpl），属性直接
 *     写到代理对象上，我们的 polyfill 就会被替换，互不干扰。
 */
;(function () {
  if (typeof window === 'undefined') return;

  /* -------- 各 API 的降级实现 -------- */
  var polyfills = {};

  // uni.request → XHR
  polyfills.request = function (opts) {
    opts = opts || {};
    var xhr = new XMLHttpRequest();
    var method = (opts.method || 'GET').toUpperCase();
    var url = opts.url || '';

    // GET/HEAD 把 data 追加到 query string
    if (opts.data && (method === 'GET' || method === 'HEAD')) {
      var qs = Object.keys(opts.data).map(function (k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(opts.data[k]);
      }).join('&');
      if (qs) url += (url.indexOf('?') >= 0 ? '&' : '?') + qs;
    }

    try {
      xhr.open(method, url, true);
    } catch (e) {
      opts.fail && opts.fail({ errMsg: 'request:fail ' + e.message });
      return;
    }

    // 设置请求头
    var header = opts.header || opts.headers || {};
    Object.keys(header).forEach(function (k) {
      try { xhr.setRequestHeader(k, header[k]); } catch (e) {}
    });

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) return;
      var statusCode = xhr.status;
      var data;
      try { data = JSON.parse(xhr.responseText); } catch (e) { data = xhr.responseText; }

      // 解析响应头
      var resHeader = {};
      try {
        (xhr.getAllResponseHeaders() || '').split('\r\n').forEach(function (line) {
          var idx = line.indexOf(':');
          if (idx > 0) resHeader[line.slice(0, idx).trim().toLowerCase()] = line.slice(idx + 1).trim();
        });
      } catch (e) {}

      var res = { statusCode: statusCode, data: data, header: resHeader };
      if (statusCode >= 200 && statusCode < 400) {
        opts.success && opts.success(res);
      } else {
        opts.fail && opts.fail({ errMsg: 'request:fail statusCode ' + statusCode });
      }
      opts.complete && opts.complete(res);
    };

    xhr.onerror = function () {
      var err = { errMsg: 'request:fail network error' };
      opts.fail && opts.fail(err);
      opts.complete && opts.complete(err);
    };

    xhr.ontimeout = function () {
      var err = { errMsg: 'request:fail timeout' };
      opts.fail && opts.fail(err);
      opts.complete && opts.complete(err);
    };

    var body = null;
    if (opts.data && method !== 'GET' && method !== 'HEAD') {
      body = typeof opts.data === 'string' ? opts.data : JSON.stringify(opts.data);
    }
    try { xhr.send(body); } catch (e) {
      opts.fail && opts.fail({ errMsg: 'request:fail ' + e.message });
    }
  };

  // getStorageSync → localStorage
  polyfills.getStorageSync = function (key) {
    try {
      var v = localStorage.getItem(key);
      if (v === null) return undefined;
      try { return JSON.parse(v); } catch (e) { return v; }
    } catch (e) { return undefined; }
  };

  polyfills.setStorageSync = function (key, data) {
    try {
      localStorage.setItem(key, typeof data === 'object' ? JSON.stringify(data) : String(data));
    } catch (e) {}
  };

  polyfills.removeStorageSync = function (key) {
    try { localStorage.removeItem(key); } catch (e) {}
  };

  // getWindowInfo → 浏览器 window
  polyfills.getWindowInfo = function () {
    return {
      windowWidth: window.innerWidth || 375,
      windowHeight: window.innerHeight || 667,
      screenWidth: window.screen ? window.screen.width : 375,
      screenHeight: window.screen ? window.screen.height : 667,
      statusBarHeight: 0,
      safeAreaInsets: { top: 0, bottom: 0, left: 0, right: 0 }
    };
  };

  // getEnterOptionsSync → 解析当前 URL query
  polyfills.getEnterOptionsSync = function () {
    var query = {};
    try {
      var search = location.search.slice(1);
      if (search) {
        search.split('&').forEach(function (pair) {
          var kv = pair.split('=');
          if (kv[0]) query[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1] || '');
        });
      }
    } catch (e) {}
    return { query: query, path: location.pathname };
  };

  /* -------- 代理对象：稳定的 window.uni -------- */
  // 创建持久代理对象，把 polyfill 方法写入
  var _proxy = {};
  Object.keys(polyfills).forEach(function (k) { _proxy[k] = polyfills[k]; });

  /**
   * 把一个 uni 对象（可能含存根）合并到 _proxy。
   * 原则：如果 polyfill 中有对应 key，则仅当对方的实现不是存根时才覆盖；
   *       其余 key 直接合并，以保留 UniApp 运行时的其他功能（路由、组件等）。
   */
  function mergeIntoProxy(srcObj) {
    if (!srcObj || typeof srcObj !== 'object') return;
    Object.keys(srcObj).forEach(function (k) {
      var val = srcObj[k];
      if (polyfills[k]) {
        // 对于我们有 polyfill 的 key：只有对方是真正的实现时才覆盖
        // 简单判断：UniApp 存根函数不含 prototype.constructor 以外的属性且长度固定
        // 最安全的方式：暂时保留我们的实现，等真实实现直接赋值给 uni.xxx 时再替换
        // 这里什么都不做——让 UniApp 运行时后续直接赋值来替换
      } else {
        _proxy[k] = val;
      }
    });
    // 把 prototype 链上的方法也复制（如 $emit/$on 等）
    try {
      var proto = Object.getPrototypeOf(srcObj);
      if (proto && proto !== Object.prototype) {
        Object.getOwnPropertyNames(proto).forEach(function (k) {
          if (k !== 'constructor' && !_proxy[k]) {
            try { _proxy[k] = proto[k].bind(srcObj); } catch (e) {}
          }
        });
      }
    } catch (e) {}
  }

  /* 拦截 window.uni 赋值 */
  try {
    Object.defineProperty(window, 'uni', {
      get: function () { return _proxy; },
      set: function (newVal) {
        // UniApp 运行时把存根对象赋给 window.uni 时在这里被拦截
        mergeIntoProxy(newVal);
      },
      configurable: true,
      enumerable: true
    });
  } catch (e) {
    // 极少数情况下 defineProperty 失败，退化为直接赋值
    window.uni = _proxy;
  }
})();
