<template>
  <div>
    <div class="flex-y-center mb-16">
      <div v-for="(item, index) in tabList" :key="index" 
      class="page-tab-item"
      :class="{'active-tab': item.name == activeTab}"
      @click="onClickTab(item.name)">{{ item.label }}</div>
    </div>
  </div>
</template>
<script>
import { mapState } from "vuex";
export default {
  name: "",
  props:{
    dataType: {
      type: Number,
      default: 1,
    }
  },
  data() {
    return {
      tabList: [
        { label: "商品品牌", name: "/admin/product/product_brand" },
        { label: "商品参数", name: "/admin/product/specs" },
        { label: "商品单位", name: "/admin/product/unitList" },
        { label: "保障服务", name: "/admin/product/ensure" },
      ],
      activeTab: this.$route.path,
    };
  },
  methods: {
    onClickTab(name) {
      if(name == this.$route.path) return
      this.$router.push(name);
    },
  },
};
</script>
<style lang="less" scoped>
.page-tab-item{
  padding: 4px 0 8px;
  margin-right: 32px;
  font-size: 14px;
  color: #515a63;
  border: 2px solid transparent;
  cursor: pointer;
}
.active-tab{
  border-bottom: 2px solid #2d8cf0;
  color: #2d8cf0;
  font-weight: 600;
}
</style>