<template>
  <!-- 装修-主题风格 -->
  <div>
    <div class="i-layout-page-header">
      <!-- 顶部标题 -->
      <PageHeader
        class="product_tabs"
        :title="$route.meta.title"
        hidden-breadcrumb
        :style="'padding-right:' + (menuCollapse ? 105 : 20) + 'px'"
      >
        <div slot="title">
          <div style="float: left">
            <span v-text="$route.meta.title" class="mr20"></span>
          </div>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <div class="flex h-668">
        <Form :label-width="80" label-position="right">
          <FormItem label="配色方案:">
            <RadioGroup v-model="themeType" @on-change="themeTypeChange">
              <Radio :label="1">系统配色</Radio>
              <Radio :label="2" class="ml-30">自定义配色</Radio>
            </RadioGroup>
          </FormItem>
          <FormItem v-show="themeType == 1">
            <div class="w-432 grid-column-4 grid-gap-16px">
              <div
                class="theme-box relative"
                v-for="(item, index) in themeList"
                :key="index"
                :class="{ 'active-box': current == index + 1 }"
                @click="themeChange(index, item)"
              >
                <p class="fs-12">{{ item.title }}</p>
                <div class="color-box flex mt-10">
                  <span
                    class="dot"
                    v-for="(color, index) in item.color"
                    :style="{ background: color}"
                    :class="{'dot-left-radius': index == 0, 'dot-right-radius': index == 2}"
                  ></span>
                </div>
                <div v-if="current == index + 1" class="jiao"></div>
                <div
                  v-if="current == index + 1"
                  class="iconfont iconduihao"
                ></div>
              </div>
            </div>
          </FormItem>
          <FormItem v-show="themeType == 2">
            <div class="gray-theme-box flex-between-center">
              <div class="">
                <div class="flex">
                  <div class="pr-33 w-70">主题色</div>
                  <ColorPicker v-model="themeColor"></ColorPicker>
                  <span class="pl-10 flex-1">{{ themeColor }}</span>
                </div>
                <div class="flex mt-14">
                  <div class="pr-33 w-70">渐变色</div>
                  <ColorPicker v-model="gradientColor"></ColorPicker>
                  <span class="pl-10 flex-1">{{ gradientColor }}</span>
                </div>
                <div class="flex mt-14">
                  <div class="pr-33 w-70">辅助色</div>
                  <ColorPicker v-model="auxColor"></ColorPicker>
                  <span class="pl-10 flex-1">{{ auxColor }}</span>
                </div>
              </div>
              <div class="v-line"></div>
              <div class="flex-col flex-center">
                <div ref="qrCodeUrl"></div>
                <div class="text--w111-999 fs-12 pt-10 lh-12px">
                  扫码查看完整演示
                </div>
              </div>
            </div>
          </FormItem>
        </Form>
        <div class="solid-line"></div>
        <div>
          <div class="fs-12 mt-14 text--w111-515A6E">预览效果</div>
          <div class="flex mt-20">
            <div class="demo-box left-bg relative">
              <div
                class="flex-y-center absolute"
                :style="{ color: themeColor, top: '231px', left: '9px' }"
              >
                <span style="font-size: 6px">到手价</span>
                <span class="fw-600" style="font-size: 6px">¥</span>
                <span class="fw-600" style="font-size: 11px">199</span>
                <span class="fw-600" style="font-size: 6px">.00</span>
              </div>
              <div class="flex absolute" style="top: 251px; left: 9px">
                <div
                  class="abs-1 flex-center"
                  :style="{ color: themeColor, background: bgLight }"
                >
                  买一送一
                </div>
                <div
                  class="abs-1 flex-center ml-4"
                  :style="{ color: themeColor, background: bgLight }"
                >
                  满1000送赠品
                </div>
                <div
                  class="abs-1 flex-center ml-4"
                  :style="{ color: themeColor, background: bgLight }"
                >
                  最高返200元
                </div>
              </div>
              <div
                class="flex row-right absolute"
                style="bottom: 18px; right: 5px"
              >
                <div
                  class="left-btn flex-center"
                  :style="{ 'background-color': auxColor }"
                >
                  加入购物车
                </div>
                <div
                  class="left-btn flex-center ml-4"
                  :style="{ 'background-color': themeColor }"
                >
                  立即购买
                </div>
              </div>
            </div>
            <div class="demo-box relative ml-24" :style="[themeBoxStyle]">
              <div
                class="flex-y-center absolute"
                :style="{ color: themeColor, top: '362px', right: '12px' }"
              >
                <span class="fw-600" style="font-size: 6px">¥</span>
                <span class="fw-600" style="font-size: 11px">199</span>
                <span class="fw-600" style="font-size: 6px">.00</span>
              </div>
              <div
                class="reply-btn absolute flex-center"
                :style="{
                  'background-color': themeColor,
                  top: '217px',
                  right: '12px',
                }"
              >
                立即评价
              </div>
              <div
                class="flex row-right absolute"
                style="bottom: 19px; right: 6px"
              >
                <div
                  class="reply-btn flex-center"
                  :style="{
                    color: themeColor,
                    border: '0.3px solid' + themeColor,
                  }"
                >
                  立即评价
                </div>
                <div
                  class="reply-btn text--w111-fff flex-center ml-4"
                  :style="{ 'background-color': themeColor }"
                >
                  立即评价
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Card>
    <Card
      :bordered="false"
      dis-hover
      class="fixed-card"
      :style="{ left: `${!menuCollapse ? '200px' : isMobile ? '0' : '80px'}` }"
    >
      <div class="acea-row row-center-wrapper">
        <Button
          class="bnt"
          type="primary"
          @click="submit"
          :loading="loadingExist"
          >保存</Button
        >
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import { colorChange, getColorChange } from "@/api/diy";
import QRCode from "qrcodejs2";
import Setting from "@/setting";
export default {
  name: "themeStyle",
  data() {
    return {
      current: 1,
      clientHeight: 0,
      loadingExist: false,
      themeType: 1, //主题风格 1系统配色 2自定义配色
      themeList: [
        {
          title: "天空蓝",
          color: ["#1DB0FC", "#22CAFD", "#5ACBFF"],
        },
        {
          title: "生鲜绿",
          color: ["#42CA4D", "#FE960F", "#4DEA4D"],
        },
        {
          title: "热情红",
          color: ["#e93323", "#FE960F", "#FF7931"],
        },
        {
          title: "魅力粉",
          color: ["#FF448F", "#282828", "#FF67AD"],
        },
        {
          title: "活力橙",
          color: ["#FE5C2D", "#FDB000", "#FF9451"],
        },
        {
          title: "高端金",
          color: ["#E0A558", "#1A1A1A", "#FFCD8C"],
        },
      ],
      themeColor: "#e93323", //自定义主题颜色
      auxColor: "#FE960F", //辅助色
      gradientColor: "#FF7931", //渐变色
      BaseURL: Setting.apiBaseURL.replace(/adminapi/, ""),
      themeBgUrl: require("@/assets/images/theme_bg_two.png"),
      copyColor:{
        themeColor: "",
        auxColor: "",
        gradientColor: "",
      }
    };
  },
  computed: {
    ...mapState("admin/layout", ["isMobile", "menuCollapse"]),
    labelWidth() {
      return this.isMobile ? undefined : 100;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
    themeBoxStyle() {
      return {
        background: `url(${this.themeBgUrl}), linear-gradient(270deg, ${this.gradientColor} 0%, ${this.themeColor} 100%)`,
        "background-size": "200px 433px, 200px 134.93px",
        "background-repeat": "no-repeat",
        "background-position": "top left, top left",
        "border-radius": "10px",
      };
    },
    bgLight() {
      //这里根据this.themeColor计算出一个该色值透明度为0.1的颜色
      const hex = this.themeColor.replace("#", "");
      const r = parseInt(hex.substring(0, 2), 16);
      const g = parseInt(hex.substring(2, 4), 16);
      const b = parseInt(hex.substring(4, 6), 16);
      return `rgba(${r}, ${g}, ${b}, 0.1)`;
    },
  },
  created() {
    this.getInfo();
  },
  mounted: function () {
    this.$nextTick(() => {
      this.clientHeight = `${document.documentElement.clientHeight}` - 250; //获取浏览器可视区域高度
      let that = this;
      window.onresize = function () {
        that.clientHeight = `${document.documentElement.clientHeight}` - 250;
      };
    });
    this.creatQrCode();
  },
  methods: {
    themeChange(index, item) {
      this.current = index + 1;
      this.themeColor = item.color[0];
      this.auxColor = item.color[1];
      this.gradientColor = item.color[2];
    },
    themeTypeChange(e) {
      if (e == 1) {
        this.themeChange(this.current - 1, this.themeList[this.current - 1]);
      }else{
        if(this.copyColor.themeColor){
          this.themeColor = this.copyColor.themeColor;
          this.auxColor = this.copyColor.auxColor;
          this.gradientColor = this.copyColor.gradientColor;
        }
        
      }
    },
    getInfo() {
      getColorChange("color_change")
        .then((res) => {
          this.current = res.data.status;
          this.themeType = res.data.color_data.theme_type;
          if (this.themeType == 1) {
            this.themeChange(
              this.current - 1,
              this.themeList[this.current - 1]
            );
          } else {
            this.copyColor = {
              themeColor: res.data.color_data.theme_color,
              auxColor: res.data.color_data.aux_color,
              gradientColor: res.data.color_data.gradient_color,
            }
            this.themeColor = res.data.color_data.theme_color;
            this.auxColor = res.data.color_data.aux_color;
            this.gradientColor = res.data.color_data.gradient_color;
          }
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    submit() {
      this.loadingExist = true;
      let data = {
        theme_type: this.themeType,
        theme_color: this.themeColor,
        aux_color: this.auxColor,
        gradient_color: this.gradientColor,
        light_color: this.bgLight,
      };
      colorChange(this.current, data)
        .then((res) => {
          this.loadingExist = false;
          this.$Message.success(res.msg);
        })
        .catch(() => {
          this.loadingExist = false;
        });
    },
    //生成二维码
    creatQrCode() {
      this.$refs.qrCodeUrl.innerHTML = "";
      let url = `${this.BaseURL}pages/index/index`;
      new QRCode(this.$refs.qrCodeUrl, {
        text: url, // 需要转换为二维码的内容
        width: 102,
        height: 102,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H,
      });
    },
  },
};
</script>

<style scoped lang="less">
.box {
  height: 40px;
  width: 100px;
  line-height: 40px;
  text-align: center;
}
.pictrue {
  width: 800px;
  height: 100%;
  margin: 10px 24px 0 0;
  img {
    width: 100%;
    height: 100%;
  }
}
.footer {
  width: 100%;
  height: 70px;
  box-shadow: 0px -2px 4px rgba(0, 0, 0, 0.03);
  background-color: #fff;
  position: fixed;
  bottom: 0;
  left: 0 z-index 9;
}
.fixed-card {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 200px;
  z-index: 99;
  box-shadow: 0 -1px 2px rgb(240, 240, 240);
}
.ml-30 {
  margin-left: 30px;
}
.w-70 {
  width: 70px;
}
.w-480 {
  width: 480px;
}
.w-432 {
  width: 432px;
}
.theme-box {
  width: 94px;
  background: #f9f9f9;
  border-radius: 4px;
  border: 1px solid #f9f9f9;
  padding: 12px;
  color: #515a6e;
  line-height: 12px;
  cursor: pointer;
  .color-box {
    width: 60px;
    height: 20px;
    .dot {
      display: inline-block;
      width: 20px;
      height: 20px;
    }
    .dot-left-radius{
      border-radius: 2px 0 0 2px;
    }
    .dot-right-radius{
      border-radius: 0 2px 2px 0;
    }
  }
  .jiao {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 0;
    height: 0;
    border-bottom: 26px solid #1890ff;
    border-left: 26px solid transparent;
  }

  .iconfont {
    position: absolute;
    bottom: 3px;
    right: 1px;
    color: #ffffff;
    font-size: 12px;
  }
}
.active-box {
  border: 1px solid #2d8cf0;
}
.pr-33 {
  padding-right: 33px;
}
.lh-12px {
  line-height: 12px;
}
.gray-theme-box {
  width: 432px;
  padding: 20px 38px 20px 22px;
  background-color: #f9f9f9;
  border-radius: 8px;
}
.left-bg {
  background-image: url(../../../assets/images/theme_bg_1.png);
  background-size: 100% 100%;
}
.demo-box {
  width: 200px;
  height: 433px;
  border-radius: 8px 8px 0 0;
}
.v-line {
  width: 1px;
  height: 110px;
  background-color: #eee;
}
.solid-line {
  width: 1px;
  height: 466px;
  background-color: #eee;
  margin: 15px 30px 0 50px;
}
.abs-1 {
  height: 12px;
  border-radius: 2px;
  padding: 0 2px;
  font-size: 6px;
}
.left-btn {
  width: 58px;
  height: 19px;
  border-radius: 10px;
  color: #fff;
  font-size: 7px;
}
.reply-btn {
  width: 38px;
  height: 15px;
  border-radius: 8px;
  font-size: 6px;
  color: #fff;
}
.h-668 {
  height: 668px;
}
.text--w111-515A6E{
  color: #515a6e;
}
</style>
