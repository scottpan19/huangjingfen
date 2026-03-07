<template>
  <div>
    <Drawer
      :value="visible"
      :closable="true"
      :styles="{ padding: '30px 35px 35px 35px' }"
      width="1000"
      @on-close="drawerChange"
    >
      <div class="h-60 flex-y-center">
        <Icon style="color: #1890ff" custom="iconfont icondingdan" size="60" />
        <div class="text pl-10">
          <div class="fs-16 fw-600" v-show="!type">
            {{ infoData.type == 1 ? "入库单" : "出库单" }}
          </div>
          <div class="fs-16 fw-600" v-show="type">盘点单</div>
          <div class="text--w111-666" v-show="!type">
            {{ infoData.type == 1 ? "入库单号" : "出库单号" }}：{{
              infoData.record_no
            }}
          </div>
          <div class="text--w111-666" v-show="type">
            盘点单号：{{ infoData.record_no }}
          </div>
        </div>
      </div>
      <div class="flex-between-center pt-20 pb-25 dotted-border" v-if="!type">
        <div>
          <div class="fs-13 lh-13px text--w111-666">
            {{ infoData.type == 1 ? "入库类型" : "出库类型" }}
          </div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.stock_type_name }}
          </div>
        </div>
        <div>
          <div class="fs-13 lh-13px text--w111-666">
            {{ infoData.type == 1 ? "入库日期" : "出库日期" }}
          </div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.record_date }}
          </div>
        </div>
        <div>
          <div class="fs-13 lh-13px text--w111-666">操作员</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.admin_name }}
          </div>
        </div>
        <div>
          <div class="fs-13 lh-13px text--w111-666">创建时间</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.create_time }}
          </div>
        </div>
        <div v-show="infoData.after_sale_no">
          <div class="fs-13 lh-13px text--w111-666">关联订单号</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.after_sale_no }}
          </div>
        </div>
      </div>
      <div class="flex-between-center pt-8 pb-25 dotted-border" v-else>
        <div>
          <div class="fs-13 lh-13px text--w111-666">盘点单号</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.record_no }}
          </div>
        </div>
        <div>
          <div class="fs-13 lh-13px text--w111-666">盘点状态</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.status ? "已完成" : "进行中" }}
          </div>
        </div>
        <div>
          <div class="fs-13 lh-13px text--w111-666">操作员</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.admin_name }}
          </div>
        </div>
        <div>
          <div class="fs-13 lh-13px text--w111-666">创建时间</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.create_time }}
          </div>
        </div>
        <div v-show="infoData.order_id">
          <div class="fs-13 lh-13px text--w111-666">关联订单号</div>
          <div class="fs-14 lh-14px dark-title pt-12">
            {{ infoData.order_id }}
          </div>
        </div>
      </div>
      <div class="py-25 dotted-border" v-show="infoData.remark">
        <div class="section-title">备注</div>
        <div class="pt-16 fs-13 text--w111-666">{{ infoData.remark }}</div>
      </div>
      <div class="py-25">
        <div class="section-title">商品信息</div>
        <el-table
          size="small"
          border
          :data="paginatedData"
          :border="false"
          class="mt-20 w-full"
          v-show="infoData.product && infoData.product.length > 0 && !type"
        >
          <el-table-column
            prop="product_id"
            label="ID"
            width="80"
          ></el-table-column>
          <el-table-column prop="product_name" label="商品名称" min-width="300">
            <template slot-scope="scope">
              <div class="imgPic acea-row">
                <viewer>
                  <div class="w-40 h-40">
                    <img
                      class="w-full h-full block"
                      v-lazy="scope.row.product_image"
                    />
                  </div>
                </viewer>
                <div class="flex-1 flex-y-center">
                  <div class="w-full line2 lh-20px pl-14">
                    {{ scope.row.product_name }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column prop="product_suk" label="商品规格" min-width="120">
            <template slot-scope="scope">
              <div class="w-full line2 flex-y-center lh-20px">
                {{ scope.row.product_suk }}
              </div>
            </template>
          </el-table-column>
          <el-table-column
            prop="good_stock"
            :label="infoData.type == 1 ? '良品入库数量' : '良品出库数量'"
            width="120"
          >
            <template slot-scope="scope">
              <span>{{
                scope.row.stock_type != "good_to_defective"
                  ? scope.row.good_stock
                  : "--"
              }}</span>
            </template>
          </el-table-column>
          <el-table-column
            prop="defective_stock"
            :label="infoData.type == 1 ? '残次品入库数量' : '残次品出库数量'"
            width="120"
            v-if="infoData.stock_type != 'purchase'"
          >
            <template slot-scope="scope">
              <span>{{
                scope.row.stock_type != "defective_to_good"
                  ? scope.row.defective_stock
                  : "--"
              }}</span>
            </template>
          </el-table-column>
        </el-table>
        <el-table
          size="small"
          border
          :data="paginatedData"
          :border="false"
          class="mt-20 w-full"
          v-show="infoData.product && infoData.product.length > 0 && type"
        >
          <el-table-column
            prop="product_id"
            label="商品ID"
            width="60"
            fixed="left"
          ></el-table-column>
          <el-table-column
            prop="product_name"
            label="商品名称"
            width="240"
            fixed="left"
          >
            <template slot-scope="scope">
              <div class="imgPic acea-row">
                <viewer>
                  <div class="w-40 h-40">
                    <img
                      class="w-full h-full block"
                      v-lazy="scope.row.product_image"
                    />
                  </div>
                </viewer>
                <div class="flex-1">
                  <div class="w-full line2 lh-20px pl-14">
                    {{ scope.row.product_name }}
                  </div>
                </div>
              </div>
            </template>
          </el-table-column>
          <el-table-column
            prop="product_suk"
            label="商品规格"
            width="120"
            fixed="left"
          >
            <template slot-scope="scope">
              <div class="w-full line2 flex-y-center lh-20px pl-14">
                {{ scope.row.product_suk }}
              </div>
            </template>
          </el-table-column>
          <el-table-column
            prop="good_stock"
            label="良品库存"
            min-width="80"
          ></el-table-column>
          <el-table-column
            prop="good_inventory_stock"
            label="良品盘点数量"
            min-width="140"
          ></el-table-column>
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
                >{{
                  scope.row.good_inventory_stock - scope.row.good_stock
                }}</span
              >
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
          ></el-table-column>
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
                >{{
                  scope.row.defective_inventory_stock -
                  scope.row.defective_stock
                }}</span
              >
            </template>
          </el-table-column>
        </el-table>
        <div class="acea-row row-right page">
          <Page
            :total="infoData.product.length"
            :current="page"
            :page-size="limit"
            show-elevator
            show-total
            @on-change="pageChange"
          />
        </div>
      </div>
    </Drawer>
  </div>
</template>
<script>
import { getInventoryRecordDetail, inventoryReadApi } from "@/api/inventory.js";
export default {
  data() {
    return {
      visible: false,
      infoData: {},
      keyword: "",
      field_key: "",
      page: 1,
      limit: 10,
      paginatedData: [], // 分页后的数据
      originalProductData: [], // 保存原始数据
      unique: "",
      type: 0,
    };
  },
  methods: {
    getInfo(id, type) {
      if (type) {
        this.type = type;
        inventoryReadApi(id).then((res) => {
          console.log(res.data);
          this.infoData = res.data;

          this.originalProductData = res.data.product.map((item) => {
            return {
              product_id: item.product_id,
              unique: item.unique,
              product_name: item.product_name,
              product_image: item.product_image,
              product_suk: item.product_suk,
              good_stock: item.good_stock,
              good_inventory_stock: item.good_inventory_stock,
              defective_inventory_stock: item.defective_inventory_stock,
              defective_stock: item.defective_stock,
            };
          });
          this.page = 1; // 重置页码
          this.handleSearch(); // 初始化分页数据
          this.visible = true;
        });
      } else {
        getInventoryRecordDetail({
          id: id,
          unique: this.unique,
        })
          .then((res) => {
            this.visible = true;
            this.infoData = res.data;
            this.originalProductData = res.data.product || []; // 保存原始数据
            this.page = 1; // 重置页码
            this.handleSearch(); // 初始化分页数据
          })
          .catch((err) => {
            this.$Message.error(err.msg);
          });
      }
    },
    pageChange(page) {
      this.page = page;
      this.handleSearch();
    },
    handleSearch() {
      // 根据关键词和字段筛选数据
      let filteredData = this.originalProductData || [];
      if (this.keyword) {
        filteredData = filteredData.filter((item) => {
          return String(item.store_name)
            .toLowerCase()
            .includes(this.keyword.toLowerCase());
        });
      }

      // 计算分页数据
      const start = (this.page - 1) * this.limit;
      const end = start + this.limit;
      this.paginatedData = filteredData.slice(start, end);
    },
    drawerChange(e) {
      this.visible = false;
    },
  },
};
</script>
<style>
.b-b {
  border-bottom: 1px solid #eeeeee;
}
.expand-row {
  margin-bottom: 16px;
}
.pl-40 {
  padding-left: 40px;
}
.lh-13px {
  line-height: 13px;
}
.lh-14px {
  line-height: 14px;
}
.dark-title {
  color: rgba(0, 0, 0, 0.85);
}
.tdinfo {
  padding-top: 40px;
  margin-left: 40px;
}
.pr-80 {
  padding-right: 80px;
}
.text-green {
  color: #168e1f;
}
.text-red {
  color: #e93323;
}
.pb-25 {
  padding-bottom: 25px;
}
.dotted-border {
  border-bottom: 1px dotted #eeeeee;
}
.py-25 {
  padding: 25px 0;
}
.section-title {
  padding-left: 10px;
  border-left: 3px solid #1890ff;
  font-size: 15px;
  line-height: 15px;
  color: #303133;
}
</style>
