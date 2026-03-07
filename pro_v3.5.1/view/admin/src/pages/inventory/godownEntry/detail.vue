<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link
            :to="{
              path: `/${
                formValidate.type == 1
                  ? 'admin/inventory/godownEntry'
                  : 'admin/inventory/placingEntry'
              }`,
            }"
          >
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span class="mr20 ml16">{{
            formValidate.type == 1 ? "添加入库单" : "添加出库单"
          }}</span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt mb79">
      <Form
        class="formValidate"
        ref="formValidate"
        :rules="ruleValidate"
        :model="formValidate"
        :label-width="85"
        label-position="right"
        @submit.native.prevent
      >
        <FormItem
          :label="formValidate.type == 1 ? '入库类型:' : '出库类型:'"
          prop="stock_type"
        >
          <RadioGroup
            v-model="formValidate.stock_type"
          >
            <Radio
              v-for="(item, index) in stockTypeOptions"
              :key="index"
              :label="item.value"
            >
              {{ item.label }}
            </Radio>
          </RadioGroup>
          <div class="text--w111-999 lh-14px">
            <span v-show="formValidate.stock_type == 'purchase'"
              >采购入库，是指企业采购的商品运抵仓库后，正式登记并纳入库存管理</span
            >
            <span v-show="formValidate.stock_type == 'return'"
              >退货入库，是指用户退回的商品，重新归入商城库存</span
            >
            <span v-show="formValidate.stock_type == 'other_in'"
              >其他入库，是指除常规的入库方式之外的特殊入库情况</span
            >
            <span v-show="formValidate.stock_type == 'defective_to_good'"
              >残次品转良品，是指将残次品库存转换成可销售的良品库存，转之后残次品的库存减少，正常商品的库存对应增加</span
            >
            <span v-show="formValidate.stock_type == 'expired_return'"
              >过期退货，指当商城的商品过期后，该商品不能继续售卖，需要在系统中创建过期退货单，库存则会相应减少</span
            >
            <span v-show="formValidate.stock_type == 'use_out'"
              >试用出库，指为满足产品试用需求，将仓库商品发出给客户或相关方的出库业务</span
            >
            <span v-show="formValidate.stock_type == 'scrap_out'"
              >报废出库，指仓库中因丧失使用价值、存在质量缺陷或达到报废标准的商品，从库存中发出并进行专门报废处置的出库业务</span
            >
            <span v-show="formValidate.stock_type == 'good_to_defective'"
              >良品转残次品，指因使用损坏、质量异常等原因不能作为正常商品售卖，转为残次品管理并对应减少库存数量的业务</span
            >
            <span v-show="formValidate.stock_type == 'other_out'"
              >其他出库，指除常规的出库方式之外的特殊出库情况</span
            >
          </div>
        </FormItem>
        <FormItem
          label="售后单号:"
          prop="order_id"
          v-if="formValidate.stock_type == 'return'"
        >
          <Input
            v-model="formValidate.order_id"
            placeholder="请输入售后订单号"
            clearable
            icon="md-attach"
            class="w-460"
            @on-click="openInfo"
          />
          <span class="pl-4 fs-12 pointer text-blue" @click="searchRefund"
            >查询</span
          >
        </FormItem>
        <FormItem
          :label="formValidate.type == 1 ? '入库日期:' : '出库日期:'"
          prop="record_date"
        >
          <DatePicker
            v-model="formValidate.record_date"
            type="date"
            :options="dateOptions"
            :placeholder="
              formValidate.type == 1 ? '请选择入库时间' : '请选择出库时间'
            "
            class="w-460"
          />
        </FormItem>
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
        <FormItem :label="formValidate.type == 1 ? '入库商品:' : '出库商品:'">
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
            <el-table-column type="selection" width="55"></el-table-column>
            <el-table-column
              prop="product_id"
              label="商品ID"
              width="60"
            ></el-table-column>
            <el-table-column prop="goodInfo" label="商品名称" width="400">
              <template slot-scope="scope">
                <div class="imgPic acea-row">
                  <viewer>
                    <div class="w-40 h-40">
                      <img
                        class="w-full h-full block"
                        v-lazy="scope.row.image"
                      />
                    </div>
                  </viewer>
                  <div class="flex-1 flex-y-center">
                    <div class="w-full line2 lh-20px pl-14">
                      {{ scope.row.store_name }}
                    </div>
                  </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="suk" label="商品规格" min-width="120">
              <template slot-scope="scope">
                <div class="w-full flex-y-center lh-20px">
                  {{ scope.row.suk }}
                </div>
              </template>
            </el-table-column>
            <el-table-column prop="suk" label="商品条形码" min-width="120">
              <template slot-scope="scope">
                <div class="w-full flex-y-center lh-20px">
                  {{ scope.row.bar_code }}
                </div>
              </template>
            </el-table-column>
            <template v-if="formValidate.type == 1">
              <el-table-column
                prop="defective_stock"
                label="残次品数量"
                min-width="120"
                v-if="
                  ['return', 'other_in', 'defective_to_good'].includes(
                    formValidate.stock_type
                  )
                "
              >
                <template slot="header" slot-scope="scope">
                  <span> 残次品数量 </span>
                  <el-popover
                    :ref="`popover-${scope.$index}`"
                    v-model="setDefectiveVisible"
                    placement="top"
                    width="254"
                    trigger="manual"
                    popper-class="defective-visible-popover"
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
                          @click="numSetConfirm('setDefectiveNum')"
                          >确认</Button
                        >
                      </div>
                    </div>

                    <span
                      class="iconfont iconbianji1"
                      slot="reference"
                      @click="openPopover(scope.$index, 'setDefectiveVisible')"
                      v-if="formValidate.stock_type == 'return'"
                    ></span>
                  </el-popover>
                </template>
                <template slot-scope="scope">
                  <InputNumber
                    v-model="scope.row.defective_stock"
                    :min="0"
                    :max="
                      formValidate.stock_type == 'return'
                        ? scope.row.cart_num - scope.row.good_stock
                        : 999999
                    "
                    :step="1"
                    controls-position="right"
                    :placeholder="'请输入数量'"
                    @on-change="defectiveChange($event, scope.$index)"
                    class="w-full"
                    v-if="formValidate.stock_type == 'return'"
                  ></InputNumber>
                  <span v-else>{{ scope.row.defective_stock }}</span>
                </template>
              </el-table-column>
              <el-table-column
                prop="good_stock"
                column-key="good_stock"
                min-width="120"
              >
                <template slot="header" slot-scope="scope">
                  <span>{{ computedStockTypeName }}</span>
                  <el-popover
                    :ref="`popover-${scope.$index}`"
                    v-model="setVisible"
                    placement="top"
                    width="254"
                    trigger="manual"
                    popper-class="set-visible-popover"
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
                        <Button @click="closeSet('setVisible')">取消</Button>
                        <Button
                          type="primary"
                          class="ml-12"
                          @click="numSetConfirm('setNum')"
                          >确认</Button
                        >
                      </div>
                    </div>

                    <span
                      class="iconfont iconbianji1"
                      slot="reference"
                      @click="openPopover(scope.$index, 'setVisible')"
                    ></span>
                  </el-popover>
                </template>
                <template slot-scope="scope">
                  <InputNumber
                    v-model="scope.row.good_stock"
                    :min="0"
                    :max="999999"
                    :step="1"
                    :precision="0"
                    controls-position="right"
                    :placeholder="'请输入数量'"
                    @on-change="limitChange($event, scope.$index)"
                    class="w-full"
                  ></InputNumber>
                  <div
                    v-if="
                      formValidate.stock_type === 'defective_to_good' &&
                      scope.row.good_stock - scope.row.defective_stock > 0
                    "
                    class="text-red"
                  >
                    数量超出：{{
                      scope.row.good_stock - scope.row.defective_stock
                    }}
                  </div>
                </template>
              </el-table-column>

              <el-table-column
                prop="defective_num"
                label="残次品入库数量"
                min-width="140"
                v-if="formValidate.stock_type == 'other_in'"
              >
                <template slot="header" slot-scope="scope">
                  <span>残次品入库数量</span>
                  <el-popover
                    :ref="`popover-${scope.$index}`"
                    v-model="defectiveNumVisible"
                    placement="top"
                    width="254"
                    trigger="manual"
                    popper-class="defective-num-popover"
                  >
                    <div class="pop-title">批量修改</div>
                    <div class="mt-14 flex-between-center">
                      <Input
                        type="number"
                        class="w-85"
                        @input="setNumReplace($event, 'defectiveNum')"
                        v-model="defectiveNum"
                      ></Input>
                      <div class="flex-1 acea-row row-right row-middle">
                        <Button @click="closeSet('defectiveNumVisible')"
                          >取消</Button
                        >
                        <Button
                          type="primary"
                          class="ml-12"
                          @click="defectiveNumSetConfirm('defective_num')"
                          >确认</Button
                        >
                      </div>
                    </div>

                    <span
                      class="iconfont iconbianji1"
                      slot="reference"
                      @click="openPopover(scope.$index, 'defectiveNumVisible')"
                    ></span>
                  </el-popover>
                </template>
                <template slot-scope="scope">
                  <InputNumber
                    v-model="scope.row.defective_num"
                    :min="0"
                    :max="999999"
                    :step="1"
                    :precision="0"
                    controls-position="right"
                    :placeholder="'请输入数量'"
                    class="w-full"
                  ></InputNumber>
                  <div
                    class="text-red"
                    v-if="
                      formValidate.stock_type == 'expired_return' &&
                      scope.row.good_stock - scope.row.stock > 0
                    "
                  >
                    数量超出：{{ scope.row.good_stock - scope.row.stock }}
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                prop="cart_num"
                label="可入库数量"
                min-width="80"
                v-if="formValidate.stock_type == 'return'"
              ></el-table-column>
            </template>
            <template v-if="formValidate.type == 2">
              <el-table-column
                prop="stock"
                label="良品库存"
                min-width="80"
              ></el-table-column>
              <el-table-column
                prop="good_stock"
                column-key="good_stock"
                min-width="120"
              >
                <template slot="header" slot-scope="scope">
                  <span>{{ computedStockTypeName }}</span>
                  <el-popover
                    :ref="`popover-${scope.$index}`"
                    v-model="setVisible"
                    placement="top"
                    width="254"
                    trigger="manual"
                    popper-class="set-visible-popover"
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
                        <Button @click="closeSet('setVisible')">取消</Button>
                        <Button
                          type="primary"
                          class="ml-12"
                          @click="numSetConfirm('setNum')"
                          >确认</Button
                        >
                      </div>
                    </div>

                    <span
                      class="iconfont iconbianji1"
                      slot="reference"
                      @click="openPopover(scope.$index, 'setVisible')"
                    ></span>
                  </el-popover>
                </template>
                <template slot-scope="scope">
                  <InputNumber
                    v-model="scope.row.good_stock"
                    :min="0"
                    :max="999999"
                    :step="1"
                    :precision="0"
                    controls-position="right"
                    :placeholder="'请输入数量'"
                    @on-change="limitChange($event, scope.$index)"
                    class="w-full"
                  ></InputNumber>
                  <div
                    class="text-red"
                    v-if="
                      [
                        'use_out',
                        'expired_return',
                        'scrap_out',
                        'good_to_defective',
                        'other_out',
                      ].includes(formValidate.stock_type) &&
                      scope.row.good_stock - scope.row.stock > 0
                    "
                  >
                    数量超出：{{ scope.row.good_stock - scope.row.stock }}
                  </div>
                </template>
              </el-table-column>
              <el-table-column
                prop="defective_stock"
                label="残次品数量"
                min-width="120"
                v-if="formValidate.stock_type != 'good_to_defective'"
              >
              </el-table-column>
              <el-table-column
                prop="defective_num"
                label="残次品出库数量"
                min-width="140"
                v-if="formValidate.stock_type != 'good_to_defective'"
              >
                <template slot="header" slot-scope="scope">
                  <span>残次品出库数量</span>
                  <el-popover
                    :ref="`popover-${scope.$index}`"
                    v-model="defectiveNumVisible"
                    placement="top"
                    width="254"
                    trigger="manual"
                    popper-class="defective-num-popover"
                  >
                    <div class="pop-title">批量修改</div>
                    <div class="mt-14 flex-between-center">
                      <Input
                        type="number"
                        class="w-85"
                        @input="setNumReplace($event, 'defectiveNum')"
                        v-model="defectiveNum"
                      ></Input>
                      <div class="flex-1 acea-row row-right row-middle">
                        <Button @click="closeSet('defectiveNumVisible')"
                          >取消</Button
                        >
                        <Button
                          type="primary"
                          class="ml-12"
                          @click="defectiveNumSetConfirm('defective_num')"
                          >确认</Button
                        >
                      </div>
                    </div>

                    <span
                      class="iconfont iconbianji1"
                      slot="reference"
                      @click="openPopover(scope.$index, 'defectiveNumVisible')"
                    ></span>
                  </el-popover>
                </template>
                <template slot-scope="scope">
                  <InputNumber
                    v-model="scope.row.defective_num"
                    :min="0"
                    :max="999999"
                    :step="1"
                    :precision="0"
                    controls-position="right"
                    :placeholder="'请输入数量'"
                    class="w-full"
                  ></InputNumber>
                  <div
                    class="text-red"
                    v-if="
                      [
                        'use_out',
                        'expired_return',
                        'scrap_out',
                        'other_out',
                      ].includes(formValidate.stock_type) &&
                      scope.row.defective_num - scope.row.defective_stock > 0
                    "
                  >
                    数量超出：{{
                      scope.row.defective_num - scope.row.defective_stock
                    }}
                  </div>
                </template>
              </el-table-column>
            </template>
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
        <Button @click="backPage()">取消</Button>
        <Button type="primary" class="ml-14" @click="handleSubmit()"
          >确定</Button
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
    <Modal
      v-model="refundModal"
      title="售后订单列表"
      footerHide
      class="paymentFooter"
      scrollable
      width="1200"
      @on-cancel="cancel"
    >
      <Form
        ref="pagination"
        inline
        :model="pagination"
        :label-width="labelWidth"
        :label-position="labelPosition"
        @submit.native.prevent
      >
        <FormItem label="售后类型：">
          <Select
            class="search-form-item"
            clearable
            v-model="pagination.apply_type"
            @on-change="orderSearch"
          >
            <Option :value="1">仅退款</Option>
            <Option :value="2">退货退款(快递退回)</Option>
            <Option :value="3">退货退款(到店退货)</Option>
            <Option :value="4">平台退款</Option>
          </Select>
        </FormItem>
        <FormItem label="处理状态：">
          <Select
            clearable
            v-model="pagination.refund_type"
            class="search-form-item"
            @on-change="orderSearch"
          >
            <Option :value="0">待处理</Option>
            <Option :value="1">仅退款</Option>
            <Option :value="2">退货退款</Option>
            <Option :value="3">拒绝退款</Option>
            <Option :value="4">同意退货</Option>
            <Option :value="5">已退货</Option>
            <Option :value="6">已退款</Option>
          </Select>
        </FormItem>
        <FormItem label="订单号：" label-for="title">
          <Input
            class="search-form-item"
            v-model="pagination.order_id"
            placeholder="请输入订单号"
            clearable
          />
        </FormItem>
        <FormItem label="物流单号：" label-for="title">
          <Input
            class="search-form-item"
            v-model="pagination.refund_express"
            placeholder="请输入物流单号"
            clearable
          />
          <Button type="primary ml14" @click="orderSearch()">查询</Button>
          <Button class="ml10" @click="resetForm()">重置</Button>
        </FormItem>
      </Form>
      <!-- 售后订单表格 -->
      <Table
        :columns="thead"
        :data="refundOrderList"
        ref="table"
        height="500"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="order_id">
          <a
            v-text="row.order_id"
            style="display: block"
            @click="changeMenu(row, '2')"
          ></a>
          <span
            v-show="row.is_del === 1 && row.delete_time == null"
            class="span-del"
            >用户已删除</span
          >
        </template>
        <template slot-scope="{ row }" slot="nickname">
          <div>
            <a @click="showUserInfo(row)">{{ row.nickname }}</a
            ><span style="color: #ed4014" v-if="row.delete_time != null">
              (已注销)</span
            >
          </div>
        </template>
        <template slot-scope="{ row }" slot="user">
          <div>用户名：{{ row.nickname }}</div>
          <div>用户ID：{{ row.uid }}</div>
        </template>
        <template slot-scope="{ row }" slot="apply_type">
          <Tag color="blue" size="medium" v-if="row.apply_type == 1"
            >仅退款</Tag
          >
          <Tag color="blue" size="medium" v-if="row.apply_type == 2"
            >退货退款(快递退回)</Tag
          >
          <Tag color="blue" size="medium" v-if="row.apply_type == 3"
            >退货退款(到店退货)</Tag
          >
          <Tag color="blue" size="medium" v-if="row.apply_type == 4"
            >平台退款</Tag
          >
        </template>
        <template slot-scope="{ row }" slot="refund_type">
          <Tag color="blue" size="medium" v-if="row.refund_type == 0"
            >待处理</Tag
          >
          <Tag color="blue" size="medium" v-if="row.refund_type == 1"
            >仅退款</Tag
          >
          <Tag color="blue" size="medium" v-if="row.refund_type == 2"
            >退货退款</Tag
          >
          <Tag color="red" size="medium" v-if="row.refund_type == 3"
            >拒绝退款</Tag
          >
          <Tag color="blue" size="medium" v-if="row.refund_type == 4"
            >商品待退货</Tag
          >
          <Tag color="blue" size="medium" v-if="row.refund_type == 5"
            >退货待收货</Tag
          >
          <Tag color="green" size="medium" v-if="row.refund_type == 6"
            >已退款</Tag
          >
        </template>
        <template slot-scope="{ row }" slot="info">
          <div class="tabBox" v-for="(val, i) in row._info" :key="i">
            <div class="tabBox_img" v-viewer>
              <img
                v-lazy="
                  val.cart_info.productInfo.attrInfo
                    ? val.cart_info.productInfo.attrInfo.image
                    : val.cart_info.productInfo.image
                "
              />
            </div>
            <a class="tabBox_tit line1" @click="goodsDetails(val)">
              <span class="font-color-red" v-if="val.cart_info.is_gift"
                >赠品</span
              >
              {{ val.cart_info.productInfo.store_name + " | " }}
              {{
                val.cart_info.productInfo.attrInfo
                  ? val.cart_info.productInfo.attrInfo.suk
                  : ""
              }}
            </a>
          </div>
        </template>
        <template slot-scope="{ row }" slot="statusName">
          <Tooltip theme="dark" max-width="300" :delay="600">
            <div v-html="row.refund_reason" class="pt5"></div>
            <div slot="content">
              <div class="pt5">退款原因：{{ row.refund_explain }}</div>
              <div v-if="row.refund_goods_explain" class="pt5">
                退货原因：{{ row.refund_goods_explain }}
              </div>
            </div>
          </Tooltip>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="refundTotal"
          :current="pagination.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="pagination.limit"
        />
        <Button type="primary" @click="confirmRowOrder" class="ml-20"
          >确定</Button
        >
      </div>
    </Modal>
  </div>
</template>
<script>
import timeOptions from "@/utils/timeOptions";
import goodsAttr from "@/components/goodsAttr";
import {
  createInventoryRecord,
  getInventoryStockRefundList,
} from "@/api/inventory.js";
import { orderRefundList } from "@/api/order";
import { formatDate } from "@/utils/validate";
export default {
  name: "godownEntryDetail",
  data() {
    return {
      options: timeOptions,
      timeVal: "",
      formValidate: {
        type: 1, // 1入库单 2出库单
        stock_type: "",
        order_id: "",
        record_date: "",
        remark: "",
      },
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
      defectiveNum: null,
      setVisible: false,
      setDefectiveNum: null,
      defectiveNumVisible: false,
      setDefectiveVisible: false,
      dateOptions: {
        disabledDate(date) {
          return date && date.valueOf() > Date.now();
        },
      },
      refundModal: false,
      pagination: {
        page: 1,
        limit: 15,
        apply_type: "",
        order_id: "",
        refund_express: "",
      },
      refundOrderList: [],
      refundTotal: 0,
      loading: false,
      thead: [
        {
          title: "订单号",
          align: "center",
          slot: "order_id",
          minWidth: 150,
        },
        {
          title: "用户信息",
          slot: "nickname",
          minWidth: 130,
        },
        {
          title: "商品信息",
          slot: "info",
          minWidth: 300,
        },
        {
          title: "实际支付",
          key: "pay_price",
          minWidth: 70,
        },
        {
          title: "发起退款时间",
          key: "add_time",
          minWidth: 110,
        },
        {
          title: "售后类型",
          slot: "apply_type",
          minWidth: 110,
        },
        {
          title: "处理状态",
          slot: "refund_type",
          minWidth: 110,
        },
        {
          title: "退款信息",
          slot: "statusName",
          minWidth: 100,
        },
      ],
      orderRow: {},
      currentid: 0,
    };
  },
  components: { goodsAttr },
  computed: {
    labelWidth() {
      return this.isMobile ? undefined : 80;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
    stockTypeOptions() {
      if (this.formValidate.type == 1) {
        return [
          { value: "purchase", label: "采购入库" },
          { value: "return", label: "退货入库" },
          { value: "other_in", label: "其他入库" },
          { value: "defective_to_good", label: "残次品转良品" },
        ];
      } else {
        return [
          { value: "expired_return", label: "过期退货" },
          { value: "use_out", label: "试用出库" },
          { value: "scrap_out", label: "报废出库" },
          { value: "good_to_defective", label: "良品转残次品" },
          { value: "other_out", label: "其他出库" },
        ];
      }
    },
    computedStockTypeName() {
      if (this.formValidate.type == 1) {
        if (this.formValidate.stock_type == "purchase") {
          return "采购入库数量";
        } else if (this.formValidate.stock_type == "return") {
          return "入库数量";
        } else if (this.formValidate.stock_type == "defective_to_good") {
          return "残次品转良品数量";
        }else{
          return "良品入库数量";
        }
      } else {
        if (this.formValidate.stock_type == "good_to_defective") {
          return "良品转残次品数量";
        } else {
          return "良品出库数量";
        }
      }
    },
  },
  watch:{
    'formValidate.stock_type'(val,oldval){
      if(oldval == 'return' || val == 'return'){
        this.tableData = [];
      }
    }
  },
  mounted() {
    this.formValidate.type = this.$route.params.type;
    this.formValidate.stock_type =
      this.formValidate.type == 1 ? "purchase" : "expired_return";
    this.formValidate.record_date = formatDate(
      new Date(Number(new Date().getTime())),
      "yyyy-MM-dd"
    );
    this.getOrderList();
  },
  methods: {
    createTime(e) {
      this.formValidate.record_date = e;
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
          // 只有当good_stock未定义时才设置为0，保留已修改的值
          if (item.good_stock === undefined) {
            this.$set(item, "good_stock", 0);
          }
          this.$set(item, "defective_num", 0);
          // if(this.formValidate.stock_type == 'other_in'){
          //   this.$set(item, "defective_stock", 0);
          // }
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
    orderSearch() {
      this.pagination.page = 1;
      this.getOrderList();
    },
    resetForm() {
      this.pagination = {
        page: 1,
        limit: 15,
        apply_type: "",
        order_id: "",
        refund_express: "",
      };
      this.getOrderList();
    },
    openPopover(index, visible) {
      this[visible] = !this[visible];

      const key = "popover-" + index;
      this.$nextTick(() => {
        document.getElementById(this.$refs[key].$refs.popper.id).style.display =
          "none";
      });
    },

    handleSubmit() {
      this.$refs.formValidate.validate((valid) => {
        if (valid) {
          if (!this.tableData.length)
            return this.$Message.error("请选择入库商品");
          const isSpecialStockType =
            (this.formValidate.type == 1 &&
              this.formValidate.stock_type == "other_in") ||
            (this.formValidate.type == 2 &&
              this.formValidate.stock_type != "good_to_defective");
          /**
           *  (this.formValidate.type == 2 &&
              this.formValidate.stock_type != "good_to_defective") ||
           */
          let params = {
            ...this.formValidate,
            product: this.tableData.map((item) => {
              const productItem = {
                product_id: item.product_id,
                unique: item.unique,
                good_stock: item.good_stock,
                defective_stock: item.defective_stock,
              };

              if (isSpecialStockType) {
                productItem.defective_stock = item.defective_num;
              }
              if (
                ["purchase", "defective_to_good", "good_to_defective"].includes(
                  this.formValidate.stock_type
                )
              ) {
                productItem.defective_stock = "";
              }

              return productItem;
            }),
          };
          createInventoryRecord(params)
            .then((res) => {
              this.$Message.success("操作成功");
              this.$router.push({
                path: `/admin/inventory/${
                  this.formValidate.type == 1 ? "godownEntry" : "placingEntry"
                }`,
              });
            })
            .catch((err) => {
              this.$Message.error(err.msg);
            });
        } else {
          this.$Message.error("请完善表单信息");
          return false;
        }
      });
    },
    backPage() {
      this.$router.push({
        path: `/admin/inventory/${
          this.formValidate.type == 1 ? "godownEntry" : "placingEntry"
        }`,
      });
    },
    setNumReplace(value, type) {
      //这里要求只能输入正整数
      this[type] = parseInt(value.replace(/[^\d]/g, "")) || 0;
    },
    closeSet(type) {
      if (type) {
        // 只关闭特定的popover
        this[type] = false;
      } else {
        // 关闭所有popover
        this.setVisible = false;
        this.setDefectiveVisible = false;
        this.defectiveNumVisible = false;
      }
      this.setNum = null;
      this.setDefectiveNum = null;
      this.defectiveNum = null;
    },
    numSetConfirm(type) {
      this.tableData.forEach((item) => {
        if (type == "setNum") {
          item.good_stock = this.setNum;
          console.log(item);
        } else {
          item.defective_stock = this.setDefectiveNum;
        }
      });
      this.$nextTick(() => {
        this.closeSet("setVisible");
        this.closeSet("setDefectiveVisible");
      });
    },
    defectiveNumSetConfirm(type) {
      this.tableData.forEach((item) => {
        item[type] = this.defectiveNum;
      });
      this.closeSet("defectiveNumVisible");
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
                product_id: productInfo.attrInfo.product_id,
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
    openInfo() {
      this.refundModal = true;
    },
    // 订单列表
    getOrderList() {
      orderRefundList(this.pagination)
        .then((res) => {
          this.loading = false;
          const { count, list, num } = res.data;
          this.refundTotal = count;
          this.refundOrderList = list;
          let radio = {
            width: 60,
            align: "center",
            render: (h, params) => {
              let id = params.row.id;
              let flag = false;
              if (this.currentid === id) {
                flag = true;
              } else {
                flag = false;
              }
              let self = this;
              return h("div", [
                h("Radio", {
                  props: {
                    value: flag,
                  },
                  on: {
                    "on-change": () => {
                      self.currentid = id;
                      self.orderRow = params.row;
                    },
                  },
                }),
              ]);
            },
          };
          //判断this.thead里面如果有radio，则不添加
          this.thead = this.thead.filter(
            (col) => !col.render || !String(col.render).includes("Radio")
          );
          this.thead.unshift(radio);
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    // 分页
    pageChange(index) {
      this.pagination.page = index;
      this.getOrderList();
    },
    confirmRowOrder() {
      this.formValidate.order_id = this.orderRow.order_id;
      this.refundModal = false;
      this.searchRefund();
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
  left: 236px;
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
.iconbianji1 {
  font-size: 12px;
  padding-left: 4px;
  cursor: pointer;
}
.tabBox {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;

  .tabBox_img {
    width: 30px;
    height: 30px;

    img {
      width: 100%;
      height: 100%;
    }
  }

  .tabBox_tit {
    width: 245px;
    height: 30px;
    line-height: 30px;
    font-size: 12px !important;
    margin: 0 2px 0 10px;
    letter-spacing: 1px;
    box-sizing: border-box;
  }
}

.tabBox + .tabBox {
  margin-top: 5px;
}
.search-form-item {
  width: 150px;
}
</style>
