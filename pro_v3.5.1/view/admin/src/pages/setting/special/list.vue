<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader
        class="product_tabs"
        :title="$route.meta.title"
        hidden-breadcrumb
      ></PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <div class="acea-row row-between-wrapper">
        <Row type="flex">
          <Col v-bind="grid">
            <div class="button acea-row row-middle">
              <Button type="primary" @click="add">添加</Button>
            </div>
          </Col>
        </Row>
      </div>
      <Table
        :columns="columns1"
        :data="list"
        ref="table"
        class="mt25"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
      >
        <template slot-scope="{ row, index }" slot="link">
          <div>/pages/annex/special/index?id={{ row.id }}</div>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row)">设计</a>
          <div v-if="row.id != 1" style="display: inline-block">
            <Divider type="vertical" />
            <a @click="del(row, index)">删除</a>
          </div>
          <div style="display: inline-block">
            <Divider type="vertical" />
            <a
              class="copy-data"
              @click="preview(row)"
              >预览</a
            >
          </div>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="limit"
        />
      </div>
    </Card>
    <Modal v-model="modal" title="预览" footer-hide>
      <div>
        <div v-viewer class="acea-row row-around code">
          <div class="acea-row row-column-around row-between-wrapper">
            <div class="QRpic" ref="qrCodeUrl"></div>
            <span class="mt10">公众号二维码</span>
          </div>
          <div class="acea-row row-column-around row-between-wrapper">
            <div class="QRpic" v-show="qrcodeImg">
              <img v-lazy="qrcodeImg" />
            </div>
            <span class="mt10">小程序二维码</span>
          </div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import Setting from "@/setting";
import { diyList, getRoutineCode, diyDel, setStatus } from "@/api/diy";
import { mapState } from "vuex";
import QRCode from "qrcodejs2";
export default {
  name: "devise_list",
  data() {
    return {
      grid: {
        xl: 7,
        lg: 7,
        md: 12,
        sm: 24,
        xs: 24,
      },
      loading: false,
      columns1: [
        {
          title: "页面ID",
          key: "id",
          minWidth: 120,
        },
        {
          title: "专题页名称",
          key: "name",
          minWidth: 120,
        },
        {
          title: "专题链接",
          slot: "link",
          minWidth: 300,
        },
        {
          title: "添加时间",
          key: "add_time",
          minWidth: 120,
        },
        {
          title: "更新时间",
          key: "update_time",
          minWidth: 120,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          minWidth: 300,
        },
      ],
      list: [],
      total: 0,
      page: 1,
      limit: 15,
      BaseURL: Setting.apiBaseURL.replace(/adminapi/, ""),
      qrcodeImg: "",
      modal: false,
    };
  },
  created() {
    this.getList();
  },
  mounted: function () {
    
  },
  methods: {
    preview(row) {
      this.modal = true;
      this.creatQrCode(row.id, row.status);
      this.routineCode(row.id);
    },
    //生成二维码
    creatQrCode(id, status) {
      this.$refs.qrCodeUrl.innerHTML = "";
      let url = `${this.BaseURL}pages/annex/special/index?id=${id}`;
      new QRCode(this.$refs.qrCodeUrl, {
        text: url, // 需要转换为二维码的内容
        width: 140,
        height: 140,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H,
      });
    },
    //小程序二维码
    routineCode(id) {
      getRoutineCode(id)
        .then((res) => {
          this.qrcodeImg = res.data.image;
        })
        .catch((err) => {
          this.$Message.error(err);
        });
    },
    // 获取列表
    getList() {
      this.loading = true;
      diyList({
        type: 1,
        page: this.page,
        limit: this.limit,
      }).then((res) => {
        this.loading = false;
        this.list = res.data.list;
        this.total = res.data.count;
      });
    },
    // 编辑
    edit(row) {
      if (row.is_diy) {
        this.$router.push({
          path: "/admin/pages/diy",
          query: { id: row.id, name: row.template_name || "moren", return_url: 2 },
        });
      } else {
        let storage = window.localStorage;
        storage.setItem("pageName", row.template_name);
        this.$store.dispatch("admin/user/getPageName");
        this.$router.push({
          path: "/admin/setting/pages/template",
          query: { id: row.id, name: row.template_name },
        });
      }
    },
    // 添加
    add() {
      this.$router.push({
        path: "/admin/pages/diy",
        query: { id: 0, name: "首页", type: 1, return_url: 2 },
      });
    },
    // 删除
    del(row) {
      let delfromData = {
        title: "删除",
        num: 2000,
        url: "diy/del/" + row.id,
        method: "DELETE",
        data: {
          type: 2,
        },
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 使用模板
    setStatus(row) {
      setStatus(row.id).then((res) => {
        this.$Message.success(res.msg);
        this.getList();
      });
    },
    pageChange(index) {
      this.page = index;
      this.getList();
    },
  },
};
</script>

<style lang="less" scoped>
.QRpic {
  width: 140px;
  height: 140px;

  img {
    width: 100%;
    height: 100%;
  }
}
</style>
