import { isSupplierPath } from "./pathUtils";
import util from "@/libs/util";

class AuthManager {
  static getToken() {
    return isSupplierPath()
      ? util.cookies.get("supplier_token")
      : util.cookies.get("token");
  }

  static setToken(token) {
    if (isSupplierPath()) {
      util.cookies.set("supplier_token", token);
    } else {
      util.cookies.set("token", token);
    }
  }

  static clearToken() {
    if (isSupplierPath()) {
      util.cookies.remove("supplier_token");
    } else {
      util.cookies.remove("token");
    }
  }
}

export default AuthManager;
