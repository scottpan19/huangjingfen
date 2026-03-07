<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link :to="{ path: '/admin/inventory/storeStock', query: { page: rowData.from_page } }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span class="mr20 ml16">库存记录</span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover :padding="20" class="ivu-mt">
      <div class="flex">
        <img :src="rowData.image" alt="" class="block w-64 h-64" />
        <div class="ml-14 fs-12">
          <div class="w-full">{{ rowData.store_name }}</div>
          <div class="w-full pt-8 text--w111-999">{{ rowData.suk }}</div>
        </div>
      </div>
    </Card>
    <Card :bordered="false" dis-hover :padding="0" class="ivu-mt">
      <div class="new_card_pd">
        <Form
          class="formValidate"
          ref="formValidate"
          inline
          :rules="ruleValidate"
          :model="formValidate"
          :label-width="75"
          label-position="left"
          @submit.native.prevent
        >
          <FormItem label="单据编号:">
            <Input
              type="text"
              v-model="formValidate.record_no"
              clearable
              class="w-250 mr-20"
              placeholder="请输入单据编号"
            ></Input>
          </FormItem>
          <FormItem label="变更类型:">
            <Select
              v-model="formValidate.stock_type"
              placeholder="请选择变更类型"
              clearable
              class="w-250 mr-20"
            >
              <Option value="other_in">其他入库</Option>
              <Option value="return">退货入库</Option>
              <Option value="purchase">采购入库</Option>
              <Option value="profit">盘盈入库</Option>
              <Option value="defective_to_good">残次品转良</Option>
              <Option value="other_out">其他出库</Option>
              <Option value="loss">盘亏出库</Option>
              <Option value="use_out">试用出库</Option>
              <Option value="scrap_out">报废出库</Option>
              <Option value="sale">销售出库</Option>
              <Option value="expired_return">过期退货</Option>
              <Option value="good_to_defective">良品转残次品</Option>
            </Select>
          </FormItem>
          <FormItem label="创建时间:">
            <DatePicker
              v-model="dataTimeVal"
              type="daterange"
              :options="options"
              format="yyyy/MM/dd"
              placeholder="请选择创建时间"
              @on-change="dataTime"
              clearable
              class="w-250 mr-20"
            />
          </FormItem>
          <FormItem :labelWidth="0">
            <Button type="primary" @click="pageChange(1)" class="ml-4"
              >查询</Button
            >
            <Button @click="exportFile" class="ml-14">导出</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <el-table
        size="small"
        :data="tableData"
        :border="false"
        @selection-change="selectionGood"
        class="w-full"
      >
        <el-table-column prop="record_no" label="单据编号" min-width="100">
          <template slot-scope="scope">
            <span class="text-blue pointer" @click="openInfo(scope.row)">{{
              scope.row.record_no
            }}</span>
          </template>
        </el-table-column>
        <el-table-column
          prop="stock_type_name"
          label="变更类型"
          min-width="100"
        ></el-table-column>
        <el-table-column
          prop="good_stock"
          label="良品出入库数量"
          min-width="100"
        >
          <template slot-scope="scope">
            <span
              :class="scope.row.good_stock > 0 ? 'text-red' : 'text-green'"
              >{{ scope.row.good_stock > 0 ? '+' : '' }}{{ scope.row.good_stock }}</span
            >
          </template>
        </el-table-column>
        <el-table-column
          prop="defective_stock"
          label="残次品出入库数量"
          min-width="100"
        >
          <template slot-scope="scope">
            <span
              :class="scope.row.defective_stock > 0 ? 'text-red' : 'text-green'"
              >{{ scope.row.defective_stock > 0 ? '+' : '' }}{{ scope.row.defective_stock }}</span
            >
          </template>
        </el-table-column>
        <el-table-column
          prop="record_date"
          label="业务时间"
          min-width="100"
        ></el-table-column>
        <el-table-column
          prop="admin_name"
          label="操作员"
          min-width="100"
        ></el-table-column>
        <el-table-column
          prop="create_time"
          label="创建时间"
          min-width="100"
        ></el-table-column>
      </el-table>
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
import timeOptions from "@/utils/timeOptions";
import goodsAttr from "@/components/goodsAttr";
import {
  getInventoryRecordList,
  exportStockDetailsApi,
} from "@/api/inventory.js";
import drawerInfo from "../components/info.vue";
import exportExcel from "@/utils/newToExcel.js";
export default {
  name: "godownEntryDetail",
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      dataTimeVal: [],
      formValidate: {
        page: 1,
        limit: 10,
        record_no: "",
        unique: "",
        stock_type: "",
      },
      total: 0,
      ruleValidate: {
        stock_type: [
          { required: true, message: "请选择入库类型", trigger: "change" },
        ],
        order_id: [
          { required: true, message: "请输入售后单号", trigger: "blur" },
        ],
        record_date: [
          {
            required: true,
            type: "date",
            message: "请选择入库时间",
            trigger: "change",
          },
        ],
      },
      tableData: [],
      modals: false,
      defaultSelected: [],
      ids: [],
      setNum: null,
      setVisible: false,
      setDefectiveNum: null,
      setDefectiveVisible: false,
      rowData: {
        store_name: "",
        image: "",
        suk: "",
      },
    };
  },
  components: { goodsAttr, drawerInfo },
  mounted() {
    this.formValidate.unique = this.$route.query.unique;
    this.rowData.store_name = this.$route.query.store_name;
    this.rowData.image = this.$route.query.image;
    this.rowData.suk = this.$route.query.suk;
    this.rowData.from_page = this.$route.query.from_page;
    this.getList();
  },
  created() {},
  methods: {
    createTime(e) {
      this.timeVal = e;
      this.formValidate.record_date = this.timeVal.join("-");
    },
    dataTime(e) {
      this.dataTimeVal = e.length && e[0] ? e : [];
      this.formValidate.create_time = this.dataTimeVal.join("-");
    },
    getList() {
      getInventoryRecordList(this.formValidate).then((res) => {
        this.tableData = res.data.list;
        this.total = res.data.count;
      });
    },
    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    },
    //导出
    async exportFile() {
      let [th, filekey, data, fileName] = [[], [], [], ""];
      let excelData = { ...this.formValidate };
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
        exportStockDetailsApi(data).then((res) => resolve(res.data));
      });
    },
    checkProduct() {
      this.modals = true;
    },
    cancel() {
      this.modals = false;
    },
    getProductId(data) {
      try {
        data.map((item) => {
          this.$set(item, "good_stock", 1);
          //defective_stock 残次品 good_stock 良品
        });
        // 过滤已存在的商品,只添加未存在的新商品
        let newData = data.filter((newItem) => {
          return !this.tableData.some(
            (existingItem) => existingItem.unique === newItem.unique
          );
        });
        // 少了哪些商品，进行删除
        let delData = this.tableData.filter((existingItem) => {
          return !data.some(
            (newItem) => newItem.unique === existingItem.unique
          );
        });
        if (delData.length) {
          this.tableData = this.tableData.filter((existingItem) => {
            return !delData.some(
              (newItem) => newItem.unique === existingItem.unique
            );
          });
        }

        if (newData.length) {
          this.tableData = [...this.tableData, ...newData];
        }
        this.modals = false;
      } catch (error) {
        console.log(error);
      }
    },
    //对象数组去重；
    unique(arr) {
      const res = new Map();
      return arr.filter((arr) => !res.has(arr.id) && res.set(arr.id, 1));
    },
    del(index) {
      this.tableData.splice(index, 1);
    },
    limitChange(e, index) {
      this.tableData[index].good_stock = e;
    },
    defectiveChange(e, index) {
      this.tableData[index].defective_stock = e;
    },
    selectionGood(e) {
      this.ids = e.map((item) => item.id);
    },
    delAll() {
      this.tableData = this.tableData.filter(
        (item) => !this.ids.includes(item.id)
      );
    },
    handleSubmit() {
      this.$refs.formValidate.validate((valid) => {
        if (valid) {
          if (!this.tableData.length) {
            this.$message.error("请选择入库商品");
            return;
          }
          let params = {
            ...this.formValidate,
            product: this.tableData.map((item) => ({
              product_id: item.product_id,
              unique: item.unique,
              good_stock: item.good_stock,
            })),
          };
          createInventoryRecord(params).then((res) => {
            this.$Message.success("操作成功");
            this.$router.push({
              path: `/admin/inventory/${
                this.formValidate.type == 1 ? "godownEntry" : "placingEntry"
              }`,
            });
          });
        } else {
          this.$Message.error("请完善表单信息");
          return false;
        }
      });
    },
    cancel() {},
    setNumReplace(value, type) {
      //这里要求只能输入正整数
      this[type] = parseInt(value.replace(/[^\d]/g, "")) || 0;
    },
    closeSet() {
      this.setVisible = false;
      this.setDefectiveVisible = false;
      this.setNum = null;
      this.setDefectiveNum = null;
    },
    numSetConfirm(type) {
      this.tableData.forEach((item) => {
        if (type == "setNum") {
          item.good_stock = this.setNum;
        } else {
          item.defective_stock = this.setDefectiveNum;
        }
      });
      this.closeSet();
    },
    searchRefund() {
      if (this.formValidate.order_id == "")
        return this.$Message.error("请输入售后单号");
      this.tableData = [];
      getInventoryStockRefundList({ order_id: this.formValidate.order_id })
        .then((res) => {
          if (res.data.length) {
            this.tableData = res.data.map((item) => {
              const productInfo = item.productInfo;
              // 直接在对象上设置属性，无需使用$set
              return {
                id: productInfo.id,
                unique: productInfo.attrInfo.unique,
                cart_num: item.cart_num,
                suk: productInfo.attrInfo.suk,
                image: productInfo.attrInfo.image,
                good_stock: 0,
                defective_stock: 0,
                store_name: productInfo.store_name,
              };
            });
          }
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    openInfo(row) {
      this.$refs.infoDrawer.getInfo(row.id);
    },
  },
};
</script>
<style>
.lh-20px {
  line-height: 20px;
}
.fixed-card {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 250px;
  z-index: 20;
  box-shadow: 0 -1px 2px rgb(240, 240, 240);

  /deep/ .ivu-card-body {
    padding: 15px 16px 14px;
  }

  .ivu-form-item {
    margin-bottom: 12px !important;
  }

  /deep/ .ivu-form-item-content {
    margin-right: 124px;
    text-align: center;
  }
}
.w-85 {
  width: 85px;
}
.h-64 {
  height: 64px;
  object-fit: cover;
}
.iconbianji1 {
  font-size: 12px;
  padding-left: 4px;
  cursor: pointer;
}
</style>
