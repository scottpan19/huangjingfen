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

      activeTab: this.$route.path,
    };
  },
  computed: {
    tabList(){
      if(this.dataType == 1){
        return [
          { label: "客户列表", name: "/admin/work/client/list" },
          { label: "企微渠道码", name: "/admin/work/channel_code" },
          { label: "欢迎语", name: "/admin/work/welcome" },
          { label: "客户群发", name: "/admin/work/client/group" },
          { label: "朋友圈列表", name: "/admin/work/client/moment" },
        ]
      }else{
        return [
          { label: "客户群列表", name: "/admin/work/client/group_chat" },
          { label: "自动拉群", name: "/admin/work/auth_group" },
          { label: "客户群群发", name: "/admin/work/group/template" },
        ]
      }
    }
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