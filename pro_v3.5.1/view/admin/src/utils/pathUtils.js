/**
 * 判断当前URL路径的第一个单词是否为'supplier'
 * @returns {boolean}
 */
export function isSupplierPath() {
  const path = window.location.pathname;
  return path.split("/")[1]
    ? path.split("/")[1].toLowerCase() === "supplier"
    : false;
}
