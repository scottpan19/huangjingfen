<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link :to="{ path: '/admin/product/recommend/' }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span class="mr20 ml16">编辑商品推荐</span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt text--w111-333">
      <Alert show-icon>
        通过配置不同的商品规则，可以实现“千人千面”的商品推荐。<br/>
        推荐规则如果同时开启，按照推荐的优先级进行排序，指定商品推荐＞个性化推荐＞商品排序推荐，当推荐的商品在多个规则中同时出现，系统会自动做去重处理。
      </Alert>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt text--w111-333">
      <div class="flex-between-center" style="cursor: pointer;">
        <div class="flex-y-center lh-20px">
          <span class="iconfont fs-12" :class="expandState.specify ? 'iconxia' : 'iconyou'"
            @click="toggleExpand('specify')"></span>
          <span class="num-tag fw-500">1</span>
          <span class="fs-14 pl-8 fw-500">指定商品推荐</span>
        </div>
        <i-switch
          v-model="formValidate.specify_recommend_status"
          :true-value="1"
          :false-value="0"
          size="large"
        >
          <span slot="open">开启</span>
          <span slot="close">关闭</span>
        </i-switch>
      </div>
      <div>
        <div v-show="expandState.specify">
          <div class="fs-12 lh-16px mt-20 pl-20">按照所指定商品的顺序推荐，拖拽对商品进行排序</div>
          <div class="acea-row mt-20 pl-20">
            <draggable
              :list="formValidate.specify_recommend_content"
              handle=".pictrue"
              animation="300"
            >
              <div
                class="pictrue"
                v-for="(item, index) in formValidate.specify_recommend_content"
                :key="index"
              >
                <img v-lazy="item.image" />
                <Button
                  shape="circle"
                  icon="md-close"
                  @click.native="bindDelete(index)"
                  class="btndel"
                ></Button>
              </div>
            </draggable>
            <div
              class="upLoad acea-row row-center-wrapper"
              @click="goodsTap"
            >
              <Icon type="ios-camera-outline" size="26" />
            </div>
          </div>
        </div>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt text--w111-333">
      <div class="flex-between-center" style="cursor: pointer;">
        <div class="flex-y-center lh-20px">
          <span class="iconfont fs-12" :class="expandState.personality ? 'iconxia' : 'iconyou'"
            @click="toggleExpand('personality')"></span>
          <span class="num-tag fw-500">2</span>
          <span class="fs-14 pl-8 fw-500">个性化推荐</span>
        </div>
        <i-switch
          v-model="formValidate.personality_recommend_status"
          :true-value="1"
          :false-value="0"
          size="large"
        >
          <span slot="open">开启</span>
          <span slot="close">关闭</span>
        </i-switch>
      </div>
      <div v-show="expandState.personality">
        <div class="fs-12 lh-16px mt-20 pl-20">推荐商品来源：通过对用户近30天浏览、加购、收藏、购买的商品，整理出与这些商品的标签、分类、品牌相同的商品
推荐规则：通过拖拽调整用户行为的排序，调整商品推荐优先级</div>
        <div class="mt-20 pl-20">
          <draggable
            :list="formValidate.personality_recommend_content"
            handle=".move-icon"
            @end="onMoveSpec"
            animation="300"
          >
            <div class="cell-item flex-y-center fs-12 pl-12 pointer" v-for="(item, index) in formValidate.personality_recommend_content" :key="index">
            <span class="iconfont iconxingzhuangjiehe move-icon fs-12 text--w111-999"></span>
            <span class="pl-8">{{item.name}}</span>
          </div>
          </draggable>
        </div>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt text--w111-333">
      <div class="flex-between-center" style="cursor: pointer;">
        <div class="flex-y-center lh-20px">
          <span class="iconfont fs-12" :class="expandState.sort ? 'iconxia' : 'iconyou'"
            @click="toggleExpand('sort')" ></span>
          <span class="num-tag fw-500">3</span>
          <span class="fs-14 pl-8 fw-500">商品排序推荐</span>
        </div>
        <i-switch
          v-model="formValidate.sort_recommend_status"
          :true-value="1"
          :false-value="0"
          size="large"
        >
          <span slot="open">开启</span>
          <span slot="close">关闭</span>
        </i-switch>
      </div>
      <div v-show="expandState.sort">
        <div class="fs-12 lh-16px mt-20 pl-20">基于商品的各项数据做排序推荐，通过拖拽排序可调整推荐优先级</div>
        <div class="mt-20 pl-20">
          <draggable
            group="specifications"
            :list="formValidate.sort_recommend_content"
            handle=".move-icon"
            @end="onMoveSpec"
            animation="300"
          >
          <div class="cell-item flex-y-center fs-12 pl-12 pointer" v-for="(item, index) in formValidate.sort_recommend_content" :key="index">
            <span class="iconfont iconxingzhuangjiehe move-icon fs-12 text--w111-999"></span>
            <span class="pl-8">{{item.name}}</span>
          </div>
          </draggable>
        </div>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="fixed-card">
      <div class="flex-center">
        <Button type="primary" class="ml-14" @click="handleSubmit()">保存</Button>
      </div>
    </Card>
    <Modal
      v-model="goodsModals"
      title="商品列表"
      footerHide
      scrollable
      width="900"
      @on-cancel="goodCancel"
    >
      <goods-list
        v-if="goodsModals"
        ref="goodslist"
        @getProductId="getProductId"
        :ischeckbox="true"
      ></goods-list>
    </Modal>
  </div>
</template>
<script>
import goodsList from "@/components/goodsList/index";
import vuedraggable from "vuedraggable";
import { productRecommendSaveApi, productRecommendInfoApi } from "@/api/product";
export default {
  name: 'recommendEdit',
  data() {
    return {
      formValidate: {
        id: 0,
        type: 0,
        status: 0,
        specify_recommend_status: 0, //指定推荐开关
        specify_recommend_content: [], //指定推荐商品
        personality_recommend_status: 0, //个性化推荐开关
        personality_recommend_content: [
          {value:1, name: '加购商品'},
          {value:2, name: '收藏商品'},
          {value:3, name: '浏览商品'},
          {value:4, name: '购买商品'},
        ], //个性化推荐你内容
        sort_recommend_status: 0, //排序推荐开关
        sort_recommend_content:[
          {value:1, name: '按照销量排序'},
          {value:2, name: '按照销售额排序'},
          {value:3, name: '按照上架时间排序'},
          {value:4, name: '按照评分排序'},
          {value:5, name: '按照浏览量排序'},
        ], //排序推荐内容
      },
      goodsModals: false,
      expandState: {
        specify: true,
        personality: true,
        sort: true
      }
    }
  },
  components: {
    goodsList,
    draggable: vuedraggable,
  },
  mounted() {
    if(this.$route.params.id){
      this.getInfo();
    }
  },
  methods:{
    toggleExpand(type) {
      this.expandState[type] = !this.expandState[type];
    },
    goodCancel() {
      this.goodsModals = false;
    },
    goodsTap() {
      this.goodsModals = true;
      this.$refs.goodslist.handleSelectAll();
    },
    bindDelete(index) {
      this.formValidate.specify_recommend_content.splice(index, 1);
    },
    getProductId(e) {
      this.goodsModals = false;
      let nArr = this.formValidate.specify_recommend_content
        .concat(e)
        .filter((element, index, self) => {
          return self.findIndex((x) => x.product_id == element.product_id) == index
        });
      this.formValidate.specify_recommend_content = nArr;
      //保证this.formValidate.specify_recommend_content的长度不超过20
      this.formValidate.specify_recommend_content = this.formValidate.specify_recommend_content.slice(0, 20);
    },
    onMoveSpec(){

    },
    handleSubmit(){
      if(!this.formValidate.id) return this.$Message.warning("仅可编辑");
      productRecommendSaveApi(this.formValidate).then(res=>{
        this.$Message.success(res.msg);
      }).catch(res=>{
        this.$Message.error(res.msg);
      })
    },
    getInfo(){
      productRecommendInfoApi(this.$route.params.id).then(res=>{
        this.formValidate = res.data;
        if(res.data.specify_recommend_content){
          this.formValidate.specify_recommend_content = res.data.specify_recommend_content;
        }else{
          this.formValidate.specify_recommend_content = [];
        }
      }).catch(res=>{
        this.$Message.error(res.msg);
      })

    }
  }
}
</script>
<style lang="less" scoped>
.lh-20px {
  line-height: 20px;
}
.text--w111-515A6E{
  color: #515A6E;
}
.cell-item{
  height: 45px;
  background-color: rgba(249,249,249,0.5);
  border-radius: 4px;
  ~ .cell-item{
    margin-top: 12px;
  }
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
}
.num-tag{
  display: inline-block;
  width: 20px;
  height: 20px;
  border-radius: 10px;
  text-align: center;
  line-height: 20px;
  background: rgba(45, 140, 239, 0.1);
  font-size: 14px;
  color: #2D8CEF;
  margin-left: 8px;
}
.ivu-alert{
  margin: 0px;
}
</style>
