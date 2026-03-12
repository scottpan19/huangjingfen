<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="pt-14 pl-14 pr-14">
        <Alert show-icon closable>
          温馨提示:1.新增商品时可选统一规格或者多规格，满足商品不同销售属性场景；2.商品销售状态分为销售中且库存足够时才可下单购买
        </Alert>
      </div>
      <!-- 条件筛选 -->
      <Form
        ref="searchForm"
        inline
        :model="searchForm"
        :label-width="85"
        label-position="right"
        @submit.native.prevent
      >
        <FormItem label="商品搜索:">
          <Input
            v-model="searchForm.store_name"
            placeholder="请输入"
            element-id="name"
            clearable
            class="input-add"
            maxlength="20"
          >
            <Select
              v-model="searchForm.field_key"
              slot="prepend"
              style="width: 80px"
              default-label="全部"
            >
              <Option value="product_id">商品 ID</Option>
              <Option value="store_name">商品名称</Option>
              <Option value="keyword">商品关键字</Option>
              <Option value="code">商品编码</Option>
              <Option value="bar_code">商品条形码</Option>
            </Select>
          </Input>
        </FormItem>
        <FormItem label="商品类型:" v-if="!isSupplier">
          <Select
            v-model="searchForm.product_type"
            @on-change="userSearchs"
            clearable
            class="input-add"
          >
            <Option :value="0">普通商品</Option>
            <Option :value="1">卡密商品</Option>
            <Option :value="3">虚拟商品</Option>
            <Option :value="4">次卡商品</Option>
          </Select>
        </FormItem>
        <!-- 报单商品筛选 (P1G-02: is_queue_goods) -->
        <FormItem label="报单商品:">
          <Select
            v-model="searchForm.is_queue_goods"
            @on-change="userSearchs"
            clearable
            class="input-add"
          >
            <Option :value="1">是</Option>
            <Option :value="0">否</Option>
          </Select>
        </FormItem>
        <FormItem label="商品分类:">
          <el-cascader
            placeholder="请选择商品分类"
            class="input-add"
            size="mini"
            v-model="searchForm.cate_id"
            :options="data1"
            :props="props"
            @change="userSearchs"
            filterable
            clearable
          >
          </el-cascader>
        </FormItem>
        <template v-if="collapse">
          <FormItem label="商品品牌:" prop="brand_id">
            <Cascader
              :data="brandData"
              placeholder="请选择商品品牌"
              change-on-select
              v-model="searchForm.brand_id"
              class="input-add"
              filterable
              clearable
              @on-change="userSearchs"
            ></Cascader>
          </FormItem>
          <FormItem label="配送方式:">
            <Select
              v-model="searchForm.delivery_type"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="1">商城配送</Option>
              <Option :value="2">到店自提</Option>
            </Select>
          </FormItem>
          <FormItem label="商品规格:">
            <Select
              v-model="searchForm.spec_type"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="0">单规格</Option>
              <Option :value="1">多规格</Option>
            </Select>
          </FormItem>
          <FormItem label="商品标签:">
            <el-cascader
              placeholder="请选择商品分类"
              class="input-add"
              size="mini"
              v-model="searchForm.store_label_id"
              :options="goodsDataLabel"
              :props="props"
              @change="userSearchs"
              filterable
              clearable
            >
            </el-cascader>
          </FormItem>
          <FormItem label="供应商:" v-if="!isSupplier">
            <Select
              v-model="searchForm.supplier_id"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option
                v-for="item in supplierList"
                :value="item.id"
                :key="item.id"
                >{{ item.supplier_name }}
              </Option>
            </Select>
          </FormItem>
          <!-- <FormItem label="付费会员:">
            <Select
              v-model="searchForm.is_vip"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="1">开启</Option>
              <Option :value="0">关闭</Option>
            </Select>
          </FormItem> -->
          <FormItem label="等级会员价:">
            <Select
              v-model="searchForm.level_type"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="1">默认比例</Option>
              <Option :value="2">自定义</Option>
            </Select>
          </FormItem>
          <FormItem label="参与返佣:">
            <Select
              v-model="searchForm.is_brokerage"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="1">参与</Option>
              <Option :value="0">不参与</Option>
            </Select>
          </FormItem>
          <FormItem label="参与活动:">
            <Select
              v-model="searchForm.activity_type"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="1">秒杀</Option>
              <Option :value="2">砍价</Option>
              <Option :value="3">拼团</Option>
            </Select>
          </FormItem>
          <FormItem label="销量:">
            <div class="input-add flex-between-center">
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.sales_range[0]"
                @on-change="validateMinValue($event, 'sales_range', 0)"
              />
              <span>~</span>
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.sales_range[1]"
                @on-change="validateMinValue($event, 'sales_range', 1)"
              />
            </div>
          </FormItem>
          <FormItem label="售价:">
            <div class="input-add flex-between-center">
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.price_range[0]"
                @on-change="validateMinValue($event, 'price_range', 0)"
              />
              <span>~</span>
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.price_range[1]"
                @on-change="validateMinValue($event, 'price_range', 1)"
              />
            </div>
          </FormItem>
          <FormItem label="库存:">
            <div class="input-add flex-between-center">
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.stock_range[0]"
                @on-change="validateMinValue($event, 'stock_range', 0)"
              />
              <span>~</span>
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.stock_range[1]"
                @on-change="validateMinValue($event, 'stock_range', 1)"
              />
            </div>
          </FormItem>
          <FormItem label="收藏数:">
            <div class="input-add flex-between-center">
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.collect_range[0]"
                @on-change="validateMinValue($event, 'collect_range', 0)"
              />
              <span>~</span>
              <Input
                type="number"
                class="w-100px"
                v-model="searchForm.collect_range[1]"
                @on-change="validateMinValue($event, 'collect_range', 1)"
              />
            </div>
          </FormItem>
          <FormItem label="适用群体:">
            <Select
              v-model="searchForm.product_clear"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option value="is_general_product">零售用户</Option>
              <Option value="is_channel_product">采购商</Option>
              <Option value="is_vip_product">付费会员</Option>
            </Select>
          </FormItem>
          <FormItem label="创建时间:">
            <DatePicker
              :editable="false"
              @on-change="createTime"
              v-model="timeVal"
              format="yyyy/MM/dd HH:mm:ss"
              type="datetimerange"
              placement="bottom-start"
              placeholder="时间选择"
              :options="options"
              class="input-add"
            ></DatePicker>
          </FormItem>
        </template>
        
        <FormItem class="ivu-text-right search-btn">
          <Button type="primary" @click="userSearchs">查询</Button>
          <Button class="ml-14" @click="resetForm">重置</Button>
          <a v-font="14" class="ivu-ml-8" @click="collapse = !collapse">
            <template v-if="!collapse">
              展开 <Icon type="ios-arrow-down" />
            </template>
            <template v-else> 收起 <Icon type="ios-arrow-up" /> </template>
          </a>
        </FormItem>
      </Form>
    </Card>
  </div>
</template>
<script>
let defaultObj = {
  field_key: "",
  product_type: "",
  cate_id: "",
  delivery_type: "",
  brand_id: [],
  page: 1,
  limit: 15,
  type: "1",
  store_name: "",
  supplier_id: "",
  store_label_id: [],
  sales_range: [],
  price_range: [],
  stock_range: [],
  collect_range: [],
  is_vip: "",
  level_type: "",
  is_brokerage: "",
  activity_type: "",
  create_range: "",
  product_clear: "",
  /** 报单商品筛选：1=是，0=否，空字符串=全部 */
  is_queue_goods: "",
}; 
import timeOptions from "@/utils/timeOptions";
import { productStoreLabel } from "@/api/product";
import { cloneDeep } from "lodash";
export default {
  name: "searchForm",
  props: {
    data1: {
      type: Array,
      default: () => [],
    },
    brandData: {
      type: Array,
      default: () => [],
    },
    supplierList: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      collapse: false,
      props: { emitPath: false, multiple: true, checkStrictly: true },
      options: timeOptions,
      searchForm: cloneDeep(defaultObj),
      timeVal: [],
      storeLabelShow: false,
      goodsDataLabel: [],
      isSupplier: this.__isSupplierPath()
    };
  },
  mounted() {
    this.storeLabel();
  },
  methods: {
    createTime(e) {
      this.timeVal = e;
      this.searchForm.create_range = this.timeVal.join("-");
    },
    storeLabel() {
      productStoreLabel()
        .then((res) => {
          this.goodsDataLabel = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    resetForm() {
      this.searchForm = cloneDeep(defaultObj);
      this.timeVal = [];
      this.$emit("on-change", this.searchForm);
    },
    userSearchs() {
      this.$emit("on-change", this.searchForm);
    },
    validateMinValue(val, field, index) {
      if (val.target.value < 0) {
        this.$nextTick(() => {
          this.$set(this.searchForm[field], index, 0);
        });
      }
    },
  },
};
</script>
<style lang="less" scoped>
.search-btn {
  position: absolute;
  right: 10px;
}
</style>
