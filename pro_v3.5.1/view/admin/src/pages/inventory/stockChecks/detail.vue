<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link :to="{ path: '/admin/inventory/stockChecks' }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span class="mr20 ml16">{{ title }}</span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt mb79">
      <Form
        class="formValidate"
        ref="formValidate"
        :model="formValidate"
        :label-width="85"
        label-position="right"
        @submit.native.prevent
      >
        <FormItem label="备注:">
          <Input
            v-model="formValidate.remark"
            type="textarea"
            :autosize="{ minRows: 2, maxRows: 5 }"
            placeholder="请输入备注"
            clearable
            class="w-460"
          />
        </FormItem>
        <FormItem label="选择商品:">
          <Button
            type="primary"
            @click="checkProduct"
            v-if="formValidate.stock_type != 'return'"
            class="mr-14"
            >选择商品</Button
          >
          <Button :disabled="!ids.length" @click="delAll">批量删除</Button>
          <el-table
            size="small"
            border
            :data="tableData"
            :border="false"
            @selection-change="selectionGood"
            class="mt-20 w-full"
            v-show="tableData.length > 0"
          >
            <el-table-column
              type="selection"
              width="55"
              fixed="left"
            ></el-table-column>
            <el-table-column
              prop="product_id"
              label="商品ID"
              width="60"
              fixed="left"
            ></el-table-column>
            <el-table-column
              prop="goodInfo"
              label="商品名称"
              width="240"
              fixed="left"
            >
              <template slot-scope="scope">
                <div class="imgPic flex-y-center">
                  <viewer>
                    <div class="w-40 h-40">
                      <img
                        class="w-full h-full block"
                        v-lazy="scope.row.image"
                      />
                    </div>
                  </viewer>
                  <div class="flex-1">
                    <div class="w-full flex-y-center line2 lh-20px pl-14">
                      {{ scope.row.store_name }}
                    </div>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column
              prop="suk"
              label="商品规格"
              width="120"
              fixed="left"
            >
              <template slot-scope="scope">
                <div class="w-full line2 flex-y-center lh-20px">
                  {{ scope.row.suk }}
                </div>
              </template>
            </el-table-column>
            <el-table-column
              label="商品条形码"
              prop="bar_code"
              min-width="120"
            />
            <el-table-column
              prop="good_stock"
              label="良品库存"
              min-width="80"
            />
            <el-table-column
              prop="good_inventory_stock"
              label="良品盘点数量"
              min-width="140"
            >
              <template slot="header" slot-scope="scope">
                <span>良品盘点数量</span>
                <el-popover
                  v-model="setVisible2"
                  placement="top"
                  width="254"
                  trigger="manual"
                >
                  <div class="pop-title">批量修改</div>
                  <div class="mt-14 flex-between-center">
                    <Input
                      type="number"
                      class="w-85"
                      @input="setNumReplace($event, 'setNum')"
                      v-model="setNum"
                    ></Input>
                    <div class="flex-1 acea-row row-right row-middle">
                      <Button @click="closeSet('setVisible2')">取消</Button>
                      <Button
                        type="primary"
                        class="ml-12"
                        @click="numSetConfirm('good_inventory_stock')"
                        >确认</Button
                      >
                    </div>
                  </div>

                  <span
                    class="iconfont iconbianji1"
                    slot="reference"
                    @click="setVisible2 = true"
                  ></span>
                </el-popover>
              </template>
              <template slot-scope="scope">
                <InputNumber
                  v-model="scope.row.good_inventory_stock"
                  :min="0"
                  :max="999999"
                  :step="1"
                  :precision="0"
                  controls-position="right"
                  placeholder="请输入数量"
                  class="w-120"
                ></InputNumber>
              </template>
            </el-table-column>
            <el-table-column
              prop="good_computed_stock"
              label="良品盈亏数量"
              min-width="120"
            >
              <template slot-scope="scope">
                <span
                  class="text-green"
                  :class="{
                    'text-red':
                      scope.row.good_inventory_stock - scope.row.good_stock < 0,
                  }"
                  v-show="scope.row.good_inventory_stock"
                  >{{
                    scope.row.good_inventory_stock - scope.row.good_stock
                  }}</span
                >
                <span v-show="!scope.row.good_inventory_stock">--</span>
              </template>
            </el-table-column>
            <el-table-column
              prop="defective_stock"
              label="残次品库存"
              min-width="120"
            ></el-table-column>
            <el-table-column
              prop="defective_inventory_stock"
              label="残次品盘点数量"
              min-width="140"
            >
              <template slot="header" slot-scope="scope">
                <span> 残次品盘点数量 </span>
                <el-popover
                  v-model="setDefectiveVisible"
                  placement="top"
                  width="254"
                  trigger="manual"
                >
                  <div class="pop-title">批量修改</div>
                  <div class="mt-14 flex-between-center">
                    <Input
                      type="number"
                      class="w-85"
                      @input="setNumReplace($event, 'setDefectiveNum')"
                      v-model="setDefectiveNum"
                    ></Input>
                    <div class="flex-1 acea-row row-right row-middle">
                      <Button @click="closeSet('setDefectiveVisible')"
                        >取消</Button
                      >
                      <Button
                        type="primary"
                        class="ml-12"
                        @click="numSetConfirm('defective_inventory_stock')"
                        >确认</Button
                      >
                    </div>
                  </div>

                  <span
                    class="iconfont iconbianji1"
                    slot="reference"
                    @click="setDefectiveVisible = true"
                  ></span>
                </el-popover>
              </template>
              <template slot-scope="scope">
                <InputNumber
                  v-model="scope.row.defective_inventory_stock"
                  :precision="0"
                  :min="0"
                  :max="999999"
                  :step="1"
                  controls-position="right"
                  placeholder="请输入数量"
                  class="w-120"
                ></InputNumber>
              </template>
            </el-table-column>
            <el-table-column label="残次品盈亏数量" min-width="120">
              <template slot-scope="scope">
                <span
                  class="text-green"
                  :class="{
                    'text-red':
                      scope.row.defective_inventory_stock -
                        scope.row.defective_stock <
                      0,
                  }"
                  v-show="scope.row.defective_inventory_stock"
                  >{{
                    scope.row.defective_inventory_stock -
                    scope.row.defective_stock
                  }}</span
                >
                <span v-show="!scope.row.defective_inventory_stock">--</span>
              </template>
            </el-table-column>
            <el-table-column label="操作" fixed="right" width="80">
              <template slot-scope="scope">
                <a @click="del(scope.$index)">删除</a>
              </template>
            </el-table-column>
          </el-table>
        </FormItem>
      </Form>
    </Card>
    <Card :bordered="false" dis-hover class="fixed-card">
      <div class="flex-center">
        <Button @click="handleSubmit(0)">保存草稿</Button>
        <Button type="primary" class="ml-14" @click="handleSubmit(1)"
          >完成盘点</Button
        >
      </div>
    </Card>
    <Modal
      v-model="modals"
      title="商品列表"
      footerHide
      class="paymentFooter"
      scrollable
      width="900"
      @on-cancel="cancel"
    >
      <goods-attr
        ref="goodSattr"
        product_type="0"
        :default-selected="defaultSelected"
        @getProductId="getProductId"
      ></goods-attr>
    </Modal>
  </div>
</template>
<script>
import timeOptions from "@/utils/timeOptions";
import goodsAttr from "@/components/goodsAttr";
import {
  saveInventoryApi,
  inventoryReadApi,
  editInventoryApi,
} from "@/api/inventory.js";
export default {
  name: "godownEntryDetail",
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      formValidate: {
        remark: "",
        status: 0,
      },
      tableData: [],
      modals: false,
      defaultSelected: [],
      ids: [],
      setNum: null,
      setVisible2: false,
      setDefectiveNum: null,
      setDefectiveVisible: false,
      title: "",
    };
  },
  components: { goodsAttr },
  mounted() {
    if (this.$route.params.id) {
      this.getInfo(this.$route.params.id);
      this.title = "编辑盘点单";
    } else {
      this.title = "新建盘点单";
    }
  },
  created() {},
  methods: {
    checkProduct() {
      this.modals = true;
    },
    cancel() {
      this.modals = false;
    },
    delAll() {
      this.tableData = this.tableData.filter(
        (item) => !this.ids.includes(item.id)
      );
    },
    getProductId(data) {
      try {
        data.map((item) => {
          this.$set(item, "good_inventory_stock", null);
          this.$set(item, "defective_inventory_stock", null);
          this.$set(item, "good_stock", item.stock);
          //defective_stock 残次品 good_stock 良品
        });
        // 过滤已存在的商品,只添加未存在的新商品
        let newData = data.filter((newItem) => {
          return !this.tableData.some(
            (existingItem) => existingItem.unique === newItem.unique
          );
        });
        // 少了哪些商品，进行删除
        // let delData = this.tableData.filter((existingItem) => {
        //   return !data.some(
        //     (newItem) => newItem.unique === existingItem.unique
        //   );
        // });
        // if (delData.length) {
        //   this.tableData = this.tableData.filter((existingItem) => {
        //     return !delData.some(
        //       (newItem) => newItem.unique === existingItem.unique
        //     );
        //   });
        // }
        if (newData.length) {
          this.tableData = this.tableData.concat(newData);
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
    selectionGood(e) {
      this.ids = e.map((item) => item.id);
    },
    handleSubmit(status) {
      if (!this.tableData.length) return this.$Message.error("请选择入库商品");
      let params = {
        status: status,
        remark: this.formValidate.remark,
        product: this.tableData.map((item) => ({
          product_id: item.product_id,
          unique: item.unique,
          good_stock: item.good_stock,
          defective_stock: item.defective_stock,
          good_inventory_stock: item.good_inventory_stock,
          defective_inventory_stock: item.defective_inventory_stock,
        })),
      };
      let apiFun = this.$route.params.id
        ? editInventoryApi(this.$route.params.id, params)
        : saveInventoryApi(params);
      apiFun
        .then((res) => {
          this.$Message.success("操作成功");
          this.$router.push({ path: `/admin/inventory/stockChecks` });
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    getInfo(id) {
      inventoryReadApi(id).then((res) => {
        this.formValidate.id = res.data.id;
        this.formValidate.status = res.data.status;
        this.formValidate.remark = res.data.remark;
        this.tableData = res.data.product.map((item) => {
          return {
            product_id: item.product_id,
            unique: item.unique,
            store_name: item.product_name,
            image: item.product_image,
            suk: item.product_suk,
            bar_code: item.product_bar_code,
            good_stock: item.good_stock,
            good_inventory_stock: item.good_inventory_stock
              ? item.good_inventory_stock
              : "",
            defective_inventory_stock: item.defective_inventory_stock
              ? item.defective_inventory_stock
              : "",
            defective_stock: item.defective_stock,
          };
        });
      });
    },
    setNumReplace(value, type) {
      //这里要求只能输入正整数
      this[type] = parseInt(value.replace(/[^\d]/g, "")) || 0;
    },
    numSetConfirm(type) {
      this.tableData.forEach((item) => {
        if (type == "good_inventory_stock") {
          item.good_inventory_stock = this.setNum;
        } else {
          item.defective_inventory_stock = this.setDefectiveNum;
        }
      });
      this.closeSet();
    },
    closeSet() {
      this.setVisible2 = false;
      this.setDefectiveVisible = false;
      this.setNum = null;
      this.setDefectiveNum = null;
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

.w-120 {
  width: 120px;
}

.w-85 {
  width: 85px;
}

.text-green {
  color: #168e1f;
}

.text-red {
  color: #e93323;
}

.iconbianji1 {
  font-size: 12px;
  padding-left: 4px;
  cursor: pointer;
}
</style>
