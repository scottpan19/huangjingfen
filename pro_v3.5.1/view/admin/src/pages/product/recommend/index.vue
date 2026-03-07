<template>
  <div>
    <div class="i-layout-page-header">
      <!-- 顶部标题 -->
      <PageHeader
        class="product_tabs"
        :title="$route.meta.title"
        hidden-breadcrumb
      >
        <div slot="title">
          <div class="float-l">
            <span v-text="$route.meta.title" class="mr20"></span>
          </div>
        </div>
      </PageHeader>
      <Card :bordered="false" dis-hover class="ivu-mt">
        <!--  -->
         <Table
            :columns="columns"
            :data="tableData"
            :loading="loading"
            highlight-row
          >
            <template slot-scope="{ row, index }" slot="name">
              <div class="w-204 flex-y-center">
                <img class="w-36 h-36" :src="picList.find(item => item.type == row.type).pic" alt="" />
                <span class="pl-12">{{ row.name }}</span>
              </div>
            </template>
            <template slot-scope="{ row, index }" slot="title">
              <span class="">{{ row.title }}</span>
              <span class="iconfont iconbianji1 fs-12 pointer pl-4" @click="editTitle(row, index)"></span>
            </template>
            <template slot-scope="{ row, index }" slot="status">
              <i-switch
                v-model="row.status"
                :true-value="1"
                :false-value="0"
                size="large"
                @on-change="showChange(row)"
              >
                <span slot="open">开启</span>
                <span slot="close">关闭</span>
              </i-switch>
            </template>
            <template slot-scope="{ row, index }" slot="action">
              <a @click="edit(row)">设置</a>
            </template>
          </Table>
      </Card>
      <Modal 
        v-model="visible" 
        title="修改推荐标题"
        footer-hide
        width="470"
        class-name="vertical-center-modal">
        <Form ref="formValidate" :model="formValidate" :label-width="75">
          <FormItem label="推荐标题:" prop="title">
            <Input v-model="formValidate.title" placeholder="请输入推荐标题"></Input>
          </FormItem>
        </Form>
        <div class="acea-row row-right">
          <Button @click="visible = false">取消</Button>
          <Button type="primary" @click="confirmEdit()" class="ml-14">确定</Button>
        </div>
        </Modal>
    </div>
  </div>
</template>
<script>
import { productRecommendListApi, productRecommendSetStatus, productRecommendEditTitle } from "@/api/product";
export default {
  name: "recommendList",
  data() {
    return {
      columns:[
        { title: " ", slot: "type", width: 50},
        { title: "页面名称", slot: "name", minWidth: 150},
        { title: "推荐标题", slot: "title", minWidth: 150},
        { title: "开启状态", slot: "status", minWidth: 140},
        { title: "更新时间", key: "update_time", minWidth: 140},
        { title: "操作", slot: "action", minWidth: 140, fixed: "right", align: "center"},
      ],
      params: {
        page:1,
        limit: 20
      },
      tableData: [],
      loading: false,
      picList:[
        {type: 5, pic: require("@/assets/images/recommend/balance_icon.png")},
        {type: 4, pic: require("@/assets/images/recommend/collection_icon.png")},
        {type: 3, pic: require("@/assets/images/recommend/level_icon.png")},
        {type: 2, pic: require("@/assets/images/recommend/pay_status_icon.png")},
        {type: 1, pic: require("@/assets/images/recommend/cart_icon.png")},
      ],
      formValidate: {
        title: "",
        id:0
      },
      visible: false,
      index: 0
    };
  },
  created() {
    this.getList();
  },
  methods: {
    getList(){
      productRecommendListApi(this.params).then((res) => {
        this.tableData = res.data.list;
        this.tableData.map(item=>{
          this.$set(item, 'title', item.title ? item.title : '猜你喜欢')
          this.$set(item, 'popVisible', false)
        })
      }).catch(err => {
        this.$Message.error(err.msg);
      })
    },
    showChange(row){
      productRecommendSetStatus(row.id, row.status).then(res=>{
        this.$Message.success(res.msg);
      }).catch(err=>{
        this.$Message.error(err.msg);
        this.params.page = 1;
        this.getList();
      })
    },
    edit(row){
      this.$router.push({
        path: "/admin/product/recommend/create/" + row.id
      })
    },
    editTitle(row, index){
      this.visible = true;
      this.formValidate.title = row.title;
      this.formValidate.id = row.id;
      this.index = index;
    },
    confirmEdit(){
      if(!this.formValidate.title) return this.$Message.error('请输入推荐标题');
      productRecommendEditTitle(this.formValidate).then(res=>{
        this.visible = false;
        this.tableData[this.index].title = this.formValidate.title;
        this.$Message.success(res.msg);
      }).catch(err=>{
        this.$Message.error(err.msg);
      })
    }
  },
};
</script>
