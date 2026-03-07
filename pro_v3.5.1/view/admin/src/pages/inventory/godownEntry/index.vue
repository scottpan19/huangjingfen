<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
        <Form
          ref="formValidate"
          inline
          :model="formValidate"
          :label-width="75"
          label-position="right"
          @submit.native.prevent
        >
          <FormItem
            :label="formValidate.type == 1 ? '入库类型：' : '出库类型：'"
            clearable
          >
            <Select
              v-model="formValidate.stock_type"
              placeholder="请选择"
              clearable
              @on-change="searchList()"
              class="input-add"
            >
              <Option
                v-for="(item, index) in stockTypeOptions"
                :key="index"
                :value="item.value"
                >{{ item.label }}</Option
              >
            </Select>
          </FormItem>
          <FormItem
            :label="formValidate.type == 1 ? '入库单号：' : '出库单号：'"
          >
            <Input
              v-model="formValidate.record_no"
              :placeholder="
                formValidate.type == 1 ? '请输入入库单号' : '请输入出库单号'
              "
              clearable
              class="input-add"
            />
          </FormItem>
          <FormItem
            :label="formValidate.type == 1 ? '入库时间：' : '出库时间：'"
          >
            <DatePicker
              v-model="timeVal"
              type="daterange"
              :options="options"
              format="yyyy/MM/dd"
              placeholder="请选择时间"
              @on-change="createTime"
              clearable
              class="input-add"
            />
          </FormItem>
          <FormItem label="创建时间：">
            <DatePicker
              v-model="dataTimeVal"
              type="daterange"
              :options="options"
              format="yyyy/MM/dd"
              placeholder="请选择创建时间"
              @on-change="dataTime"
              clearable
              class="input-add"
            />
          </FormItem>
          <FormItem :label-width="0">
            <Button type="primary" @click="searchList">查询</Button>
            <Button @click="reset" class="ml-14">重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 操作 -->
      <Button type="primary" @click="add">{{
        formValidate.type == 1 ? "新建入库" : "新建出库"
      }}</Button>
      <Button
        :disabled="!checkUidList.length && isAll == 0"
        class="ml-14"
        @click="exports"
        >{{ formValidate.type == 1 ? "导出入库明细" : "导出出库明细" }}</Button
      >
      <!-- 商品列表表格 -->
      <vxe-table
        ref="xTable"
        class="mt-20"
        :loading="loading"
        row-id="id"
        :checkbox-config="{ reserve: true }"
        @checkbox-all="checkboxAll"
        @checkbox-change="checkboxItem"
        :data="dataList"
      >
        <vxe-column type="checkbox" width="100">
          <template #header>
            <div>
              <Dropdown transfer @on-click="allPages">
                <a href="javascript:void(0)" class="acea-row row-middle">
                  <span
                    >全选({{
                      isAll == 1
                        ? total - checkUidList.length
                        : checkUidList.length
                    }})</span
                  >
                  <Icon type="ios-arrow-down"></Icon>
                </a>
                <template #list>
                  <DropdownMenu>
                    <DropdownItem name="0">当前页</DropdownItem>
                    <DropdownItem name="1">所有页</DropdownItem>
                  </DropdownMenu>
                </template>
              </Dropdown>
            </div>
          </template>
        </vxe-column>
        <vxe-column
          field="record_no"
          :title="formValidate.type == 1 ? '入库单号' : '出库单号'"
          min-width="120"
        ></vxe-column>
        <vxe-column
          field="stock_type_name"
          :title="formValidate.type == 1 ? '入库类型' : '出库类型'"
          min-width="150"
        >
          <template #default="{ row }">
            <div>{{ row.stock_type_name }}</div>
            <div
              v-if="['return', 'sale'].includes(row.stock_type)"
              class="text-blue pointer"
              @click="getOrderInfo(row)"
            >
              {{ row.after_sale_no }}
            </div>
          </template>
        </vxe-column>
        <vxe-column
          field="record_date"
          :title="formValidate.type == 1 ? '入库日期' : '出库日期'"
          min-width="120"
        ></vxe-column>
        <vxe-column
          field="admin_name"
          title="操作员"
          min-width="120"
        ></vxe-column>
        <vxe-column
          field="create_time"
          title="创建时间"
          min-width="120"
        ></vxe-column>
        <vxe-column field="remark" title="备注" min-width="120"></vxe-column>
        <vxe-column title="操作" width="150" fixed="right" align="center">
          <template #default="{ row }">
            <span class="text-blue pointer" @click="getInfo(row)">详情</span>
            <Divider type="vertical" />
            <span class="text-blue pointer" @click="setRemark(row)">备注</span>
          </template>
        </vxe-column>
      </vxe-table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="formValidate.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="formValidate.limit"
        />
      </div>
    </Card>
    <drawerInfo ref="infoDrawer"></drawerInfo>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  getInventoryRecordList,
  getInventoryRecordRemark,
  exportStockApi,
} from "@/api/inventory.js";
import timeOptions from "@/utils/timeOptions";
import exportExcel from "@/utils/newToExcel.js";
import drawerInfo from "../components/info.vue";
export default {
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      dataTimeVal: [],
      formValidate: {
        page: 1,
        limit: 15,
        stock_type: "", // 出入库类型：purchase=采购入库，return=退货入库，other_in=其他入库，defective_to_good=残次品转良
        record_no: "", // 出入库单号
        record_date: "", // 出入库时间
        create_time: "", // 创建时间
        type: 1, // 1入库单 2出库单
      },
      dataList: [],
      loading: false,
      total: 0,
      recordNumberList: [],
      recordNumber: 0,
      showRecord: false,
      isAll: 0, // 是否全选 0否 1是
      checkUidList: [],
      isCheckBox: false,
      showInfo: false,
      infoData: {},
      type: 1,
    };
  },
  components: {
    drawerInfo,
  },
  watch: {
    "$route.path": {
      handler(newPath) {
        this.isAll = 0;
        this.checkUidList = [];
        if (newPath == "/admin/inventory/godownEntry") {
          this.type = 1;
        } else {
          this.type = 2;
        }
        this.formValidate = {
          page: 1,
          limit: 15,
          stock_type: "",
          record_no: "",
          record_date: "",
          create_time: "",
          type: this.type, // 1入库单 2出库单
        };
        this.timeVal = [];
        this.dataTimeVal = [];
        this.getList();
      },
      deep: true,
      immediate: true, // 可选：是否在组件创建时立即执行一次
    },
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    stockTypeOptions() {
      if (this.formValidate.type == 1) {
        return [
          { value: "profit", label: "盘盈入库" },
          { value: "purchase", label: "采购入库" },
          { value: "return", label: "退货入库" },
          { value: "other_in", label: "其他入库" },
          { value: "defective_to_good", label: "残次品转良" },
        ];
      } else {
        return [
          { value: "loss", label: "盘亏出库" },
          { value: "expired_return", label: "过期退货" },
          { value: "use_out", label: "试用出库" },
          { value: "scrap_out", label: "报废出库" },
          { value: "sale", label: "销售出库" },
          { value: "good_to_defective", label: "良品转残次品" },
          { value: "other_out", label: "其他出库" },
        ];
      }
    },
  },
  methods: {
    createTime(e) {
      this.timeVal = e;
      this.formValidate.record_date = e[0] ? e.join("-") : "";
    },
    dataTime(e) {
      this.dataTimeVal = e;
      this.formValidate.create_time = e[0] ? e.join("-") : "";
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `marketing/card/gift/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    getRecord(card_id) {
      this.$router.push({
        path: "/admin/marketing/giftCard/record",
        query: {
          card_id,
        },
      });
    },
    //添加
    add() {
      this.$router.push({
        path: `/admin/inventory/${
          this.formValidate.type == 1 ? "godownEntry" : "placingEntry"
        }/detail/${this.formValidate.type}`,
      });
    },
    getList() {
      getInventoryRecordList(this.formValidate)
        .then((res) => {
          this.dataList = res.data.list;
          this.total = res.data.count;
          this.$nextTick(function () {
            if (this.isAll == 1) {
              if (this.isCheckBox) {
                this.$refs.xTable.setAllCheckboxRow(true);
              } else {
                this.$refs.xTable.setAllCheckboxRow(false);
              }
            } else {
              let obj = this.$refs.xTable.getCheckboxReserveRecords(true);
              if (
                !this.checkUidList.length ||
                this.checkUidList.length <= obj.length
              ) {
                this.$refs.xTable.setAllCheckboxRow(false);
              }
            }
          });
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    },
    // 查询
    searchList() {
      this.formValidate.page = 1;
      this.getList();
    },
    reset() {
      this.formValidate = {
        page: 1,
        limit: 15,
        stock_type: "",
        record_no: "",
        record_date: "",
        create_time: "",
        type: this.type, // 1入库单 2出库单
      };
      this.timeVal = [];
      this.dataTimeVal = [];
      this.getList();
    },
    cardStatusChange(row) {
      cardGiftStatusUpdateApi(row.id, row.status)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    getRecordNumber(id) {
      giftCardRecordNumberApi(id)
        .then((res) => {
          this.recordNumberList = res.data.list;
          this.recordNumber = res.data.count;
          this.showRecord = true;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    //导出
    async exports() {
      let [th, filekey, data, fileName] = [[], [], [], ""];
      let excelData = { ...this.formValidate };
      this.$set(excelData, "ids", this.checkUidList.join(","));
      this.$set(excelData, "all", this.isAll);
      excelData.page = 1;
      delete excelData.limit;
      for (let i = 0; i < excelData.page + 1; i++) {
        let lebData = await this.getExcelData(excelData);
        if (!fileName) fileName = lebData.filename;
        if (!filekey.length) {
          filekey = lebData.filekey;
        }
        if (!th.length) th = lebData.header;
        if (lebData.export.length) {
          data = data.concat(lebData.export);
          excelData.page++;
        } else {
          exportExcel(th, filekey, fileName, data);
          return;
        }
      }
    },
    getExcelData(data) {
      return new Promise((resolve) => {
        exportStockApi(data).then((res) => resolve(res.data));
      });
    },
    checkboxItem(e) {
      let id = parseInt(e.rowid);
      let index = this.checkUidList.indexOf(id);
      if (index !== -1) {
        this.checkUidList = this.checkUidList.filter((item) => item !== id);
      } else {
        this.checkUidList.push(id);
      }
    },
    checkboxAll() {
      // 获取选中当前值
      let obj2 = this.$refs.xTable.getCheckboxRecords(true);
      // 获取之前选中值
      let obj = this.$refs.xTable.getCheckboxReserveRecords(true);
      if (
        this.isAll == 0 &&
        this.checkUidList.length <= obj.length &&
        !this.isCheckBox
      ) {
        obj = [];
      }
      obj = obj.concat(obj2);
      let ids = [];
      obj.forEach((item) => {
        ids.push(parseInt(item.id));
      });
      this.checkUidList = ids;
      if (!obj2.length) {
        this.isCheckBox = false;
      }
    },
    allPages(e) {
      this.isAll = e;
      console.log(e);
      if (e == 0) {
        this.$refs.xTable.toggleAllCheckboxRow();
        this.checkboxAll();
      } else {
        if (!this.isCheckBox) {
          this.$refs.xTable.setAllCheckboxRow(true);
          this.isCheckBox = true;
          this.isAll = 1;
        } else {
          this.$refs.xTable.setAllCheckboxRow(false);
          this.isCheckBox = false;
          this.isAll = 0;
        }
        this.checkUidList = [];
      }
    },
    getInfo(row) {
      this.$refs.infoDrawer.getInfo(row.id);
    },
    setRemark(row) {
      this.$modalForm(getInventoryRecordRemark(row.id)).then(() =>
        this.getList()
      );
    },
    // 获取详情表单数据
    getOrderInfo(row) {
      this.$nextTick(() => {
        const baseUrl = location.origin;
        const orderId = row.after_sale_no;
        const path =
          row.stock_type === "return"
            ? "/admin/order/refund"
            : "/admin/order/list";

        window.open(`${baseUrl}${path}?order_id=${orderId}`, "_blank");
      });
    },
  },
};
</script>
