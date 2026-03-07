<template>
  <i-link
    class="i-layout-header-logo"
    :class="[{ 'i-layout-header-logo-stick': !isMobile }]"
    :to="toUrl"
  >
    <img :src="logo" />
  </i-link>
</template>
<script>
import { mapState } from "vuex";
export default {
  name: "iHeaderLogo",
  computed: {
    ...mapState("admin/layout", ["isMobile", "headerTheme", "menuCollapse"]),
  },
  data() {
    return {
      logo: require("@/assets/images/logo.png"),
      logoSmall: require("@/assets/images/logo-small.png"),
      isSupplier: this.__isSupplierPath(),
      toUrl: "",
    };
  },
  watch: {
    isSupplier: {
      handler: function (val) {
        this.toUrl = val ? "/supplier/statistic/order" : "/admin/home/";
      },
      immediate: true,
    },
  },
  mounted() {
    this.getLogo();
  },
  methods: {
    getLogo() {
      this.$store
        .dispatch("admin/db/get", {
          dbName: "sys",
          path: "user.info",
          user: true,
        })
        .then((res) => {
          this.logo = res.logo ? res.logo : this.logo;
          this.logoSmall = res.logoSmall ? res.logoSmall : this.logoSmall;
        });
    },
  },
};
</script>
<style scoped>
.i-layout-header-logo-stick {
  width: 236px;
}
</style>
