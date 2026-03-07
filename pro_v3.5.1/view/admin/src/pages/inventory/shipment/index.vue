<template>
  <div>
    <div class="flex-y-center mb-16">
      <div
        class="page-tab-item"
        :class="{ 'active-tab': tableForm.stock_type == '1' }"
        @click="onClickTab(1)"
      >
        入库记录
      </div>
      <div
        class="page-tab-item"
        :class="{ 'active-tab': tableForm.stock_type == '2' }"
        @click="onClickTab(2)"
      >
        出库记录
      </div>
    </div>
    <Card :bordered="false" dis-hover :padding="0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
        <Form
          inline
          :model="tableForm"
          :label-width="75"
          label-position="right"
          @submit.native.prevent
        >
          <FormItem label="商品信息：">
            <Input
              placeholder="请输入商品名称/ID/商品编码/条形码"
              v-model="tableForm.product_name"
              class="w-250"
            />
          </FormItem>
          <FormItem label="时间选择:">
            <DatePicker
              v-model="timeVal"
              type="daterange"
              :options="options"
              format="yyyy/MM/dd"
              :placeholder="
                tableForm.stock_type == '1'
                  ? '请选择入库时间'
                  : '请选择出库时间'
              "
              @on-change="createTime"
              clearable
              class="input-add"
            />
            <Button type="primary" @click="searchData()" class="ml-14"
              >查询</Button
            >
            <Button class="ml-14" @click="reset()">重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <cards-data
      :cardLists="overallStatistics"
      v-if="overallStatistics.length"
    ></cards-data>
    <Card
      :bordered="false"
      dis-hover
      :class="overallStatistics.length ? '' : 'ivu-mt'"
    >
      <el-table
        size="small"
        border
        :data="processedData"
        :border="false"
        class="w-full"
      >
        <el-table-column
          prop="product_id"
          label="商品ID"
          width="60"
        ></el-table-column>
        <el-table-column label="商品名称" min-width="200">
          <template slot-scope="scope">
            <div class="w-full line2 lh-20px">
              {{ scope.row.product_name }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="商品规格" min-width="140">
          <template slot-scope="scope">
            <div class="w-full line2 flex-y-center h-40 lh-20px">
              {{ scope.row.sku_name }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="商品条形码" prop="bar_code" min-width="120">
          <template slot-scope="scope">
            <span>{{ scope.row.bar_code || "--" }}</span>
          </template>
        </el-table-column>
        <el-table-column
          v-for="column in dynamicColumns"
          :key="column.prop"
          :prop="column.prop"
          :label="column.label"
          min-width="100"
        >
        </el-table-column>
      </el-table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="tableForm.page"
          :page-size="tableForm.limit"
          show-elevator
          show-total
          @on-change="pageChange"
        />
      </div>
    </Card>
  </div>
</template>
<script>
import timeOptions from "@/utils/timeOptions";
import {
  getInventoryStatisticsApi,
  getInventoryOverallStatisticsApi,
} from "@/api/inventory";
import cardsData from "@/components/cards/cards";
export default {
  data() {
    return {
      options: timeOptions,
      tableForm: {
        page: 1,
        limit: 15,
        stock_type: "1",
        product_name: "",
        record_date: "",
      },
      total: 0,
      tableData: [],
      timeVal: [],
      loading: false,
      overallStatistics: {},
    };
  },
  components: {
    cardsData,
  },
  computed: {
    /**
     * 动态生成列的定义
     */
    dynamicColumns() {
      // 假设所有行的 result 结构都相同，我们基于第一行数据来定义列
      if (
        !this.tableData ||
        this.tableData.length === 0 ||
        !this.tableData[0].result
      ) {
        return [];
      }
      // 新数据结构: label 为列标题，prop 为该列对应的数值
      // 使用 label 作为列标题(label)和数据键(prop)
      return this.tableData[0].result.map((item) => ({
        label: item.label, // 例如: "采购入库" (作为列标题)
        prop: item.label, // 使用标题字符串作为数据访问的 key
      }));
    },
    /**
     * 将原始数据转换为 el-table 需要的扁平结构
     */
    processedData() {
      return this.tableData.map((row) => {
        const newRow = { ...row };
        if (row.result) {
          row.result.forEach((item) => {
            // 将 result 数组中的每个对象转换为 newRow 的一个属性
            // 例如: newRow['采购入库'] = 20 (数值)
            newRow[item.label] = item.prop;
          });
        }
        delete newRow.result; // 删除不再需要的原始 result 数组
        return newRow;
      });
    },
  },
  created() {
    this.getList();
    this.getOverallStatistics();
  },
  methods: {
    createTime(e) {
      console.log(e, "eee");
      this.timeVal = e;
      this.tableForm.record_date =
        e.length && e[0] ? this.timeVal.join("-") : "";
    },
    onClickTab(name) {
      this.timeVal = [];
      this.tableForm.page = 1;
      this.tableForm.stock_type = name;
      this.tableForm.product_name = "";
      this.tableForm.record_date = "";
      this.getList();
      this.getOverallStatistics();
    },
    pageChange(page) {
      this.tableForm.page = page;
      this.getList();
    },
    searchData() {
      this.tableForm.page = 1;
      this.getList();
      this.getOverallStatistics();
    },
    reset() {
      this.timeVal = [];
      this.tableForm.product_name = "";
      this.tableForm.record_date = "";
      this.tableForm.page = 1;
      this.getList();
      this.getOverallStatistics();
    },
    getList() {
      this.loading = true;
      getInventoryStatisticsApi(this.tableForm)
        .then((res) => {
          this.loading = false;
          this.tableData = res.data.list;
          this.total = res.data.count;
        })
        .catch((err) => {
          this.loading = false;
          this.$Message.error(err.msg);
        });
    },
    getOverallStatistics() {
      getInventoryOverallStatisticsApi(this.tableForm)
        .then((res) => {
          this.overallStatistics = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
  },
};
</script>
<style lang="less" scoped>
.page-tab-item {
  padding: 4px 0 8px;
  margin-right: 32px;
  font-size: 14px;
  color: #515a63;
  border: 2px solid transparent;
  cursor: pointer;
}
.active-tab {
  border-bottom: 2px solid #2d8cf0;
  color: #2d8cf0;
  font-weight: 600;
}
</style>
