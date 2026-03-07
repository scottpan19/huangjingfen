// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import Vue from "vue";
import VueRouter from "vue-router";
import iView from "view-design";

import util from "@/libs/util";

import Setting from "@/setting";

import store from "@/store/index";

// 路由数据
import routes from "./routes";

import { includeArray } from "@/libs/system";

Vue.use(VueRouter);

/**
 * 重写路由的push方法
 */
const routerPush = VueRouter.prototype.push;
VueRouter.prototype.push = function push(location) {
  return routerPush.call(this, location).catch((error) => error);
};

// 生成带supplier前缀的路由函数
function generateSupplierRoutes(
  routes,
  prefix = "supplier",
  split = "",
  isChild = false
) {
  return routes.map((route) => {
    let newRoute;
    if (isChild) {
      newRoute = {
        ...route,
        name: route.name ? `supplier_${route.name}` : undefined,
        meta: {
          ...route.meta,
          isSupplierRoute: true,
        },
      };
    } else {
      newRoute = {
        ...route,
        path: route.path.replace(Setting.roterPre, Setting.routePreSupplier),
        name: route.name ? `supplier_${route.name}` : undefined,
        meta: {
          ...route.meta,
          isSupplierRoute: true,
        },
      };
    }

    // 递归处理子路由
    if (route.children) {
      newRoute.children = generateSupplierRoutes(route.children, "", "", true); // 子路由不需要再加前缀
    }

    return newRoute;
  });
}

// 动态添加Supplier路由
const supplierRoutes = generateSupplierRoutes(routes, Setting.routePreSupplier, "");

// 导出路由 在 main.js 里使用
const router = new VueRouter({
  routes: [...routes],
  mode: Setting.routerMode,
});
router.addRoutes(supplierRoutes);
setTimeout(() => {
  console.log(router, "11");
}, 1000);
/**
 * 路由拦截
 * 权限验证
 */

router.beforeEach(async (to, from, next) => {
  if (to.fullPath.indexOf("kefu") != -1) {
    return next();
  }
  const supplier_access = localStorage.getItem("supplier_unique_auth")
    ? localStorage.getItem("supplier_unique_auth").split(",")
    : [];
  const access = localStorage.getItem("unique_auth")
    ? localStorage.getItem("unique_auth").split(",")
    : [];

  // 需要身份验证的路由
  if (to.matched.some((route) => route.meta.auth)) {
    return await handleAuthenticatedRoute(to, next, supplier_access, access);
  }
  // 不需要身份验证的路由
  return handlePublicRoute(to, next);
});
// 处理需要身份验证的路由
async function handleAuthenticatedRoute(to, next, supplier_access, access) {
  const db = await store.dispatch("admin/db/database", { user: true });
  const token = Vue.prototype.__getToken();

  if (!token || token === "undefined") {
    store.dispatch("admin/db/databaseClear", { user: true });
    return next({
      name: "login",
      query: { redirect: to.fullPath },
    });
  }

  const isPermission = includeArray(
    to.meta.auth,
    to.meta.isSupplierRoute ? supplier_access : access
  );

  return next(isPermission ? undefined : { name: "403" });
}
// 处理公共路由
function handlePublicRoute(to, next) {
  const hasMenus =
    store.state.admin.menus.menusName &&
    store.state.admin.menus.menusName.length;
  const isLoginPage = [
    `${Setting.roterPre}/login`,
    `${Setting.routePreSupplier}/login`,
    "/app/upload",
  ].includes(to.path);

  if (hasMenus || isLoginPage) {
    return next();
  }
}
router.afterEach((to) => {
  // if (Setting.showProgressBar) iView.LoadingBar.finish();
  // 多页控制 打开新的页面
  store.dispatch("admin/page/open", to);

  // 更改标题
  util.title({
    title: to.meta.title,
  });
  // 返回页面顶端
  window.scrollTo(0, 0);
});

export default router;
