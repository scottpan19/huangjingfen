<template>
  <div>
    <Form :model="formData" :label-width="80">
        <FormItem label="是否参与:">
          <RadioGroup v-model="formData.is_brokerage">
            <Radio :label="0">不参与返佣</Radio>
            <Radio :label="1">参与返佣</Radio>
          </RadioGroup>
        </FormItem>
        <FormItem label="返佣设置:" v-show="formData.is_brokerage">
          <RadioGroup v-model="formData.is_sub" @on-change="changeSubType">
            <Radio :label="0">默认比例</Radio>
            <Radio :label="1">自定义佣金</Radio>
          </RadioGroup>
          <div class="fs-12 text--w111-999" v-show="formData.is_sub">切换到默认比例时，表格中编辑的返佣金额会被清空，请谨慎操作</div>
        </FormItem>
        <FormItem label="付费会员:">
          <Switch
            v-model="formData.is_vip"
            :true-value="1"
            :false-value="0"
            size="large"
          >
            <span slot="open">开启</span>
            <span slot="close">关闭</span>
          </Switch>
        </FormItem>
        <FormItem label="等级会员:">
          <RadioGroup
            v-model="formData.level_type"
            @on-change="changeLevelType"
          >
            <Radio :label="1">默认价格</Radio> 
            <Radio :label="2">自定义</Radio>
          </RadioGroup>
        </FormItem>
        <FormItem :label-width="0" v-if="specType === 1">
          <el-table
            size="small"
            border
            :data="attrData"
            style="width: 100%"
          >
            <el-table-column
              prop="suk"
              label="产品规格"
              min-width="120"
              align="center"
              fixed="left"
            >
              <template slot-scope="scope">
                <div class="line2">{{ scope.row.attr_arr.join(',') }}</div>
              </template>
            </el-table-column>
            <el-table-column
              prop="price"
              label="售价"
              min-width="120"
              align="center"
              fixed="left"
            ></el-table-column>
            <el-table-column min-width="120" align="center" v-if="formData.is_brokerage">
              <template slot="header" slot-scope="scope">
                <span>一级返佣</span>
                <el-popover
                  ref="popoverRef_one"
                  placement="top"
                  width="254"
                  trigger="click"
                  v-if="formData.is_sub == 1">
                  <div class="pop-title">批量设置一级返佣</div>
                  <div class="mt-14">
                    <RadioGroup v-model="brokerageSetType">
                      <Radio :label="0">指定价格</Radio>
                      <Radio :label="1">折扣</Radio>
                    </RadioGroup>
                  </div>
                  <div class="mt-14 flex-between-center">
                    <Input type="number" @input="brokerageReplace" class="w-85" v-model="brokerage">
                      <template #append>
                        <span>{{ brokerageSetType ? '%' : '元' }}</span>
                      </template>
                    </Input>
                    <div class="flex-1 acea-row row-right row-middle">
                      <Button @click="closePop">取消</Button>
                      <Button type="primary" class="ml-12"
                        @click="brokerageOneSetUp">确认</Button
                      >
                    </div>
                  </div>

                  <span class="iconfont iconbianji1" slot="reference"></span>
                </el-popover>
              </template>
              <template slot-scope="scope">
                <div v-show="formData.is_sub == 1">
                  <Input v-model="scope.row.brokerage" @on-change="brokerageRowReplace(scope.row)">
                    <template #append>
                      <span>元</span>
                    </template>
                  </Input>
                  <div class="flex-x-center red"
                    v-show=" Number(scope.row.brokerage) > Number(scope.row.price)">佣金不可大于售价</div>
                </div>
                <div v-show="formData.is_sub == 0">
                  <div class="flex-x-center">{{store_brokerage_ratio * 100}}% </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column min-width="120" align="center" v-if="formData.is_brokerage">
              <template slot="header" slot-scope="scope">
                <span>二级返佣</span>
                <el-popover
                  ref="popoverRef_two"
                  placement="top"
                  width="254"
                  trigger="click"
                  v-if="formData.is_sub == 1">
                  <div class="pop-title">批量设置二级返佣</div>
                  <div class="mt-14">
                    <RadioGroup v-model="brokerageSetType">
                      <Radio :label="0">指定价格</Radio>
                      <Radio :label="1">折扣</Radio>
                    </RadioGroup>
                  </div>
                  <div class="mt-14 flex-between-enter">
                    <Input type="number" class="w-85" v-model="brokerage_two">
                      <template #append>
                        <span>{{ brokerageSetType ? '%' : '元' }}</span>
                      </template>
                    </Input>
                    <div class="flex-1 acea-row row-right row-middle">
                      <Button @click="closePop">取消</Button>
                      <Button type="primary" class="ml-12"
                        @click="brokerageTwoSetUp">确认</Button
                      >
                    </div>
                  </div>
                  <span class="iconfont iconbianji1" slot="reference"></span>
                </el-popover>
              </template>
              <template slot-scope="scope">
                <div v-show="formData.is_sub == 1">
                  <Input v-model="scope.row.brokerage_two" @on-change="brokerageTwoRowReplace(scope.row)">
                    <template #append>
                      <span>元</span>
                    </template>
                  </Input>
                  <div class="flex-x-center red"
                    v-show=" Number(scope.row.brokerage_two) > Number(scope.row.price)">佣金不可大于售价</div>
                </div>
                <div v-show="formData.is_sub == 0">
                  <div class="flex-x-center">{{store_brokerage_two * 100}}% </div>
                </div>
              </template>
            </el-table-column>
            <el-table-column
              min-width="140"
              align="center"
              v-if="formData.is_vip == 1"
            >
              <template slot="header" slot-scope="scope">
                <span>付费会员价</span>
                <el-popover
                  v-model="vipVisible"
                  placement="top"
                  width="254"
                  trigger="manual"
                >
                  <div class="pop-title">批量修改本列</div>
                  <div class="mt-14">
                    <RadioGroup v-model="vipSetType">
                      <Radio :label="0">指定价格</Radio>
                      <Radio :label="1">折扣</Radio>
                      <Radio :label="2">减现</Radio>
                    </RadioGroup>
                  </div>
                  <div class="mt-14 flex-between-center">
                    <Input type="number" class="w-85" @input="vipPriceReplace" v-model="vipSetNum">
                      <template #append>
                        <span v-show="vipSetType == 0">元</span>
                        <span v-show="vipSetType == 1">%</span>
                        <span v-show="vipSetType == 2">元</span>
                      </template>
                    </Input>
                    <div class="flex-1 acea-row row-right row-middle">
                      <Button @click="closeVipSet">取消</Button>
                      <Button type="primary" class="ml-12"
                        @click="vipSetConfirm">确认</Button
                      >
                    </div>
                  </div>
                  
                  <span class="iconfont iconbianji1" slot="reference" @click="vipVisible = true"></span>
                </el-popover>
              </template>
              <template slot-scope="scope">
                <Input type="number" v-model="scope.row.vip_price" @on-change="vipRowReplace(scope.row)">
                  <template #append>
                    <span>元</span>
                  </template>
                </Input>
                <!-- <div class="flex-x-center red" v-show="scope.row.vip_price == 0">会员价不可为0</div> -->
                <div class="flex-x-center red" v-show="Number(scope.row.vip_price) > Number(scope.row.price)">会员价不可大于售价</div>
              </template>
            </el-table-column>
            <el-table-column
              min-width="140"
              align="center"
              v-for="(item, i) in levelList"
              :key="i"
            >
              <template slot="header" slot-scope="scope">
                <span>{{ item.name }}会员</span>
                <el-popover
                 :ref="'popoverRef_' + i"
                  placement="top"
                  width="254"
                  trigger="click"
                  v-if="formData.level_type == 2"
                >
                  <div class="pop-title">批量修改本列</div>
                  <div class="mt-14">
                    <RadioGroup v-model="levelSetType">
                      <Radio :label="0">指定价格</Radio>
                      <Radio :label="1">折扣</Radio>
                      <Radio :label="2">减现</Radio>
                    </RadioGroup>
                  </div>
                  <div class="mt-14 flex-between-center">
                    <Input type="number" @input="levelPriceReplace" class="w-85" v-model="levelSetNum">
                      <template #append>
                        <span v-show="levelSetType == 0">元</span>
                        <span v-show="levelSetType == 1">%</span>
                        <span v-show="levelSetType == 2">元</span>
                      </template>
                    </Input>
                    <div class="flex-1 acea-row row-right row-middle">
                      <Button @click="closeLevelSet(i)">取消</Button>
                      <Button type="primary" class="ml-12"
                        @click="levelSetConfirm(i)">确认</Button
                      >
                    </div>
                  </div>
                  <span class="iconfont iconbianji1" slot="reference" @click="vipVisible = false"></span>
                </el-popover>
              </template>
              <template slot-scope="scope">
                <div v-show="formData.level_type == 2">
                   <Input type="number" 
                    v-model="scope.row.level_price[i].price" 
                    @on-change="levelRowReplace(scope.row,i)">
                    <template #append>
                      <span>元</span>
                    </template>
                  </Input>
                  <!-- <div class="flex-x-center red" 
                    v-show="scope.row.level_price[i].price == 0">会员价不可为0</div> -->
                  <div class="flex-x-center red" 
                    v-show="Number(scope.row.level_price[i].price) > Number(scope.row.price)">会员价不可大于售价</div>
                </div>
                <div v-show="formData.level_type == 1">
                  <div class="flex-x-center">{{scope.row.level_price[i].price}}%</div>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </FormItem>
        <div v-else>
          <FormItem label="商品售价:" prop="price">
            <Input v-model="attrDataDan.price" disabled placeholder="请输入商品价格" class="w-250" />
          </FormItem>
          <div v-shouw="formData.is_brokerage == 1">
            <FormItem label="一级返佣:" >
              <Input text="number" v-model="attrDataDan.brokerage" placeholder="请输入商品分销" class="w-250" v-if="formData.is_sub == 1" />
              <span v-else>{{ (store_brokerage_ratio * 100).toFixed(2) }}%</span>
            </FormItem>
            <FormItem label="二级返佣:" >
              <Input text="number" v-model="attrDataDan.brokerage_two" placeholder="请输入商品二级分销" class="w-250" v-if="formData.is_sub == 1" />
              <span v-else>{{ (store_brokerage_two * 100).toFixed(2) }}%</span>
            </FormItem>
          </div>
          <FormItem label="付费会员:" v-show="formData.is_vip == 1">
            <Input text="number" v-model="attrDataDan.vip_price" placeholder="请输入会员价" class="w-250" />
          </FormItem>
          <FormItem :label="`用户等级${i + 1}:`" v-for="(item, i) in attrDataDan.level_price" :key="i" 
            v-show="formData.level_type == 2">
            <Input text="number" v-model="item.price" placeholder="请输入会员价" class="w-250" v-show="formData.level_type == 2"></Input>
            <span v-show="formData.level_type == 1">{{ item.price }}%</span>
          </FormItem>
        </div>
      </Form>
  </div>
</template>
<script>
import { cloneDeep } from 'lodash-es';
import { productGetLevelListApi } from "@/api/product.js";
export default {
  name: "vipPriceSet",
  data() {
    return {
      formData: {
        is_brokerage: 0,
        is_sub: 0,
        is_vip: 0,
        level_type: 1,
      },
      brokerage: "",
      brokerage_two: "",
      store_brokerage_ratio: 0,
      store_brokerage_two: 0,
      loading: false,
      attrData: [],
      disabled: false,
      levelList: [],
      vipSetType: 0,
      vipSetNum: "",
      levelSetType: 0,
      levelSetNum: "",
      vipVisible: false,
      brokerageSetType: 0,
      attrDataDan: {
        price: null,
        brokerage: "",
        brokerage_two: "",
        vip_price: "",
        level_price: [],
      },
    };
  },
  props: {
    attrValue: {
      type: Array,
      default: () => [],
    },
    specType: {
      type: [String, Number],
      default: 0,
    },
    baseInfo: {
      type: Object,
      default: () => {},
    },
    successData: {
      type: Boolean,
      default: false,
    },
    attrDan:{
      type: Object,
      default: () => {},
    }
  },
  watch: {
    attrValue: {
      handler(val) {
        if (val) {
          const [, ...rest] = val;
          this.attrData = cloneDeep(rest);
          setTimeout(()=>{
            this.init();
          },200)
        }
      },
      deep: true
    },
    attrDan: {
      handler(val) {
        if (val) {
          console.log(val,'val');
          this.attrDataDan.price = val.price;
        }
      },
      deep: true
    },
    successData: {
      handler(val) {
        if (val) {
          let keys = Object.keys(this.formData);
          keys.map((i) => {
            this.formData[i] = this.baseInfo[i];
            this.attrDataDan = this.baseInfo.attr;
          });
        }
      },
      immediate: true,
      deep: true,
    },
  },
  created() {
    productGetLevelListApi().then(res => {
      this.store_brokerage_ratio = res.data.store_brokerage_ratio;
      this.store_brokerage_two = res.data.store_brokerage_two;
      this.levelList = res.data.level_list;
      console.log(this.formData);
    });
  },
  methods: {
    brokerageReplace(value){
      this.brokerage = this.cleanPrice(value);
    },
    brokerageRowReplace(item){
      item.brokerage = this.cleanPrice(item.brokerage);
    },
    brokerageOneSetUp(){
      if(this.brokerageSetType == 1 && this.brokerage > 100) return this.$Message.error("折扣不可超过100");
      this.attrData.map(item=>{
        if(this.brokerageSetType == 0){
          item.brokerage = this.brokerage
        }else{
          item.brokerage = (this.brokerage/100 * item.price).toFixed(2);
        }
      })
      this.closePop();
    },
    closePop(){
      this.$refs.popoverRef_one.doClose();
      this.$refs.popoverRef_two.doClose();
      this.brokerage_two = "";
      this.brokerage = ""
    },
    brokerageTwoSetUp(){
      if(this.brokerageSetType == 1 && this.brokerage_two > 100) return this.$Message.error("折扣不可超过100");
      this.attrData.map(item=>{
        if(this.brokerageSetType == 0){
          item.brokerage_two = this.brokerage_two
        }else{
          item.brokerage_two = (this.brokerage_two/100 * item.price).toFixed(2);
        }
      })
      this.closePop();
    },
    brokerageTwoRowReplace(item){
       item.brokerage_two = this.cleanPrice(item.brokerage_two);
    },
    changeSubType(val){
      if(this.spec_type){
        this.attrData.map(item=>{
          item.brokerage = val == 0 ? this.store_brokerage_ratio : (item.price * this.store_brokerage_ratio).toFixed(2);
          item.brokerage_two = val == 0 ? this.store_brokerage_two : (item.price * this.store_brokerage_two).toFixed(2);
        })
      }else{
        this.attrDataDan.brokerage = (this.attrDataDan.price * this.store_brokerage_ratio).toFixed(2);
        this.attrDataDan.brokerage_two = (this.attrDataDan.price * this.store_brokerage_two).toFixed(2);
      }
    },
    vipPriceReplace(value){
      this.vipSetNum = this.cleanPrice(value);
    },
    levelPriceReplace(value){
      this.levelSetNum = this.cleanPrice(value);
    },
    submitForm() {
      let isSuccess = true,step = true;
      if(this.formData.level_type == 2){
        this.attrData.forEach(item=>{
          item.level_price.forEach(val=>{
            if(val.price == 0){
              isSuccess = false;
            }
            if( Number(val.price) > Number(item.price)){
              step = false;
            }
          })
          if(item.vip_price == 0 && this.formData.is_vip){
            isSuccess = false;
          }
          if( Number(item.vip_price) > Number(item.price)  && this.formData.is_vip){
            step = false;
          }
        })
      }
      // if(!isSuccess) return this.$Message.error("会员价不可为0");
      if(!step) return this.$Message.error("会员价不可大于售价");
      this.disabled = true;
    },
    closeVipSet() {
      // this.$refs.vipSetPopover.doClose();
      // this.$refs["vipSetPopover_" + 9][0].doClose(); //关闭的
      this.vipVisible = false;
      this.vipSetType = 0;
      this.vipSetNum = "";
    },
    closeLevelSet(i) {
      this.$refs["popoverRef_" + i][0].doClose(); //关闭的
      this.levelSetType = 0;
      this.levelSetNum = "";
    },
    vipSetConfirm(){
      // if(this.vipSetNum == 0) return this.$Message.error("会员价不可为0");
      if(this.vipSetType == 1 && this.vipSetNum > 100) return this.$Message.error("折扣不可超过100");
      this.attrData.map((item) => {
      if(this.vipSetType == 0){
        item.vip_price = this.vipSetNum;
      }else if(this.vipSetType == 1){
        item.vip_price =( this.vipSetNum/100 * item.price).toFixed(2);
      }else{
        item.vip_price = item.price - this.vipSetNum;
      }
      });
      this.closeVipSet();
    },
    levelSetConfirm(index){
      // if(this.levelSetNum == 0) return this.$Message.error("等级会员价不可为0");
      if(this.levelSetType == 1 && this.levelSetNum > 100) return this.$Message.error("折扣不可超过100");
      this.attrData.map((item) => {
      if(this.levelSetType == 0){
          item.level_price[index].price = this.levelSetNum;
      }else if(this.levelSetType == 1){
        item.level_price[index].price =( this.levelSetNum/100 * item.price).toFixed(2);
      }else{
        item.level_price[index].price = item.price - this.levelSetNum;
      }
      });
      this.closeLevelSet(index);
    },
    onCancel() {
      this.closeVipSet();
      this.levelSetType = 0;
      this.levelSetNum = "";
      this.$emit("close");
    },
    changeLevelType(type) {
      if(this.specType){
        this.attrData.forEach(item => {
          const newLevelPrice = this.levelList.map(val => ({
            id: val.id,
            discount: val.discount,
            // price: type == 1 ? val.discount * 100 : item.price
            // 下面注释的这行可以在选择自定义方式时读取等级的比例乘以售价，相较于直接读取售价可以读出等级的折扣比例
            price: type == 1 ? val.discount * 100 : (item.price * val.discount).toFixed(2)
          }));
          this.$set(item, 'level_price', newLevelPrice);
        });
      }else{
        const newLevelPrice = this.levelList.map(val => ({
          id: val.id,
          price: type == 1 ? val.discount * 100 : (this.attrDataDan.price * val.discount).toFixed(2)
        }));
        this.$set(this.attrDataDan, 'level_price', newLevelPrice);
      }
    },
    init(){
      let type = this.formData.level_type;
      if(this.specType) {
        if(type == 1) {
          this.attrData.forEach(item => {
            const newLevelPrice = this.levelList.map(val => ({
              id: val.id,
              discount: val.discount,
              // price: type == 1 ? val.discount * 100 : item.price
              // 下面注释的这行可以在选择自定义方式时读取等级的比例乘以售价，相较于直接读取售价可以读出等级的折扣比例
              price: type == 1 ? val.discount * 100 : item.price
            }));
            this.$set(item, 'level_price', newLevelPrice);
          });
        }
        
      }else{
        if(type == 1){
          const newLevelPrice = this.levelList.map(val => ({
            id: val.id,
            price: val.discount * 100
          }));
          this.$set(this.attrDataDan, 'level_price', newLevelPrice);
        }else{
          console.log(this.attrDataDan);
        }
      }
    },
    cleanPrice(value) {
      // 移除非数字和非小数点的字符
      let cleanedValue = value.replace(/[^\d.]/g, '');
      // 确保只有一个小数点
      let parts = cleanedValue.split('.');
      if (parts.length > 2) {
        cleanedValue = parts[0] + '.' + parts.slice(1).join('');
      }
      // 确保小数点后最多有两位数字
      if (cleanedValue.includes('.')) {
        let [integerPart, decimalPart] = cleanedValue.split('.');
        cleanedValue = integerPart + '.' + decimalPart.slice(0, 2);
      }
      return cleanedValue;
    },
    vipRowReplace(row){
      row.vip_price = this.cleanPrice(row.vip_price);
    },
    levelRowReplace(row,i){
      row.level_price[i].price = this.cleanPrice(row.level_price[i].price);
    },
  },
};
</script>
<style scoped lang="less">
/deep/.ivu-input-group-append {
  background: #fff;
  font-size: 12px;
}
/deep/.ivu-input-group .ivu-input {
  border-right: 0;
}
.iconbianji1 {
  font-size: 12px;
  padding-left: 4px;
  cursor: pointer;
}
.w-250 {
  width: 250px;
}
.pop-title {
  font-size: 14px;
  font-weight: bold;
  margin-bottom: 14px;
}
.red{
  color: #e93323;
}
.h-23{
  height: 23px;
}
.w-85{
  width: 85px;
}
</style>