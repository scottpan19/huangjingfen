<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
        <Form
          inline
          :model="params"
          :label-width="75"
          label-position="right"
          @submit.native.prevent
        >
          <FormItem label="商品信息：">
            <Input
              placeholder="请输入商品名称/ID/商品编码/条形码"
              v-model="params.keyword"
              clearable
              class="w-250"
            />
          </FormItem>
          <FormItem label="当前库存:">
            <div class=" flex-y-center">
              <Input
                type="number"
                clearable
                class="w-100px"
                v-model="params.stock_range[0]"
                @on-change="validateMinValue($event, 0)"
              />
              <span class="px-4">~</span>
              <Input
                type="number"
                clearable
                class="w-100px"
                v-model="params.stock_range[1]"
                @on-change="validateMinValue($event, 1)"
              />
            </div>
          </FormItem>
          <FormItem label="时间选择:">
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
          <FormItem :label-width="0">
            <Button type="primary" @click="searchList" class="ml-14"
              >查询</Button>
              <Button @click="reset" class="ml-14"
              >重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <cards-data :cardLists="overallStatistics" v-if="overallStatistics.length"></cards-data>
    <Card :bordered="false" dis-hover :class="overallStatistics.length ? '' : 'ivu-mt'">
      <Table
        ref="table"
        :columns="columns"
        :data="dataList"
        :loading="loading"
        no-data-text="暂无数据"
        no-filtered-data-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="store_name">
          <div class="imgPic acea-row">
            <viewer>
              <div class="w-40 h-40">
                <img class="w-full h-full block" v-lazy="row.image" />
              </div>
            </viewer>
            <div class="flex-1 flex-y-center">
              <div class="w-full line2 lh-20px pl-14">
                {{ row.store_name }}
              </div>
            </div>
          </div>
        </template>
        <template slot-scope="{ row }" slot="suk">
          <div class="w-full line2 flex-y-center lh-20px">{{ row.suk }}</div>
        </template>
        <template slot-scope="{ row }" slot="code">
          <div>{{ row.code || '--' }}</div>
        </template>
        <template slot-scope="{ row }" slot="bar_code">
          <div>{{ row.bar_code || '--' }}</div>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="getInfo(row)">出入库记录</a>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="params.page"
          :page-size="params.limit"
          show-elevator
          show-total
          @on-change="pageChange"
        />
      </div>
    </Card>
  </div>
</template>
<script>
import { getInventoryDetailList, getInventoryProductStatisticsApi } from "@/api/inventory.js";
import timeOptions from "@/utils/timeOptions";
import cardsData from "@/components/cards/cards";
export default {
  name: "getInventoryDetailList",
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      params: {
        page: 1,
        limit: 10,
        keyword: "",
        stock_range: ['',''],
        time: '',
      },
      columns: [
        { title: "商品ID", key: "product_id", width: 60 },
        { title: "商品名称", slot: "store_name",  minWidth: 200 },
        { title: "商品规格", slot: "suk",  minWidth: 120 },
        { title: "商品编号", slot: "code", width: 120 },
        { title: "商品条形码", slot: "bar_code", width: 120 },
        { title: "良品库存", key: "stock", width: 120 },
        { title: "残次品库存", key: "defective_stock", width: 120 },
        { title: "操作", slot: "action", width: 120, fixed: "right" },
      ],
      total: 0,
      dataList: [],
      loading: false,
      overallStatistics: []
    };
  },
  components: {
    cardsData,
  },
  created() {
    this.params.page = this.$route.query.page || 1;
    this.getList();
    this.getStatistics();
  },
  methods: {
    createTime(e){
      this.params.time = e.join('-');
    },
    getStatistics() {
      let params = {...this.params};
      params.stock_range = params.stock_range.length ? params.stock_range.join('-') : '';
      getInventoryProductStatisticsApi(params).then((res) => {
        this.overallStatistics = res.data;
      });
    },
    getList() {
      let params = {...this.params};
      params.stock_range = params.stock_range.length ? params.stock_range.join('-') : '';
      getInventoryDetailList(params).then((res) => {
          console.log(res);
          this.dataList = res.data.list;
          this.total = res.data.count;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    pageChange(index) {
      this.params.page = index;
      this.getList();
    },
    reset(){
      this,this.params = {
        page: 1,
        limit: 10,
        keyword: "",
        time: "",
        stock_range: ['',''],
      };
      this.timeVal = [];
      this.getList();
      this.getStatistics();
    },
    searchList() {
      this.params.page = 1;
      this.getList();
      this.getStatistics();
    },
    getInfo(row){
      let params = {
        unique: row.unique,
        store_name: row.store_name,
        image: row.image,
        suk: row.suk,
        from_page: this.params.page,
      };
      this.$router.push({
        path: `/admin/inventory/storeStock/detail`,
        query: params,
      });
    },
    validateMinValue(val, index){
      this.params.stock_range[index] = val.target.value.replace(/[^\d]/g, '');
    }
  },
};
</script>
