<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
        <Form
          inline
          :model="params"
          :label-width="85"
          label-position="right"
          @submit.native.prevent
        >
          <FormItem label="盘点单号:">
            <Input
              placeholder="请输入盘点单号"
              v-model="params.record_no"
              class="w-250"
            />

          </FormItem>
          <FormItem label="盘点状态:">
            <Select
              v-model="params.status"
              placeholder="请选择"
              clearable
              @on-change="searchList()"
              class="w-250"
            >
              <Option :value="1">已完成</Option>
              <Option :value="0">进行中</Option>
            </Select>
          </FormItem>
          <FormItem label="创建时间:">
            <DatePicker
              v-model="dataTimeVal"
              type="daterange"
              :options="options"
              format="yyyy/MM/dd"
              placeholder="请选择创建时间"
              @on-change="dataTime"
              clearable
              class="w-250"
            />
          </FormItem>
          <FormItem :labelWidth="0">
            <Button type="primary" class="ml-4" @click="searchList()">查询</Button>
            <Button class="ml-14" @click="reset()">重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <div>
        <!-- 操作 -->
      <Button type="primary" @click="add">新建盘点单</Button>
      <Button :disabled="!checkUidList.length" class="ml-14" @click="exports">导出盘点明细</Button>
      </div>
      <vxe-table
        ref="xTable"
        class="mt-20"
        :loading="loading"
        row-id="id"
        :checkbox-config="{ reserve: true }"
        @checkbox-all="checkboxAll"
        @checkbox-change="checkboxItem"
        :data="dataList"
      >
        <vxe-column type="checkbox" width="100">
          <template #header>
            <div>
              <Dropdown transfer @on-click="allPages">
                <a href="javascript:void(0)" class="acea-row row-middle">
                  <span
                    >全选({{
                      isAll == 1
                        ? total - checkUidList.length
                        : checkUidList.length
                    }})</span
                  >
                  <Icon type="ios-arrow-down"></Icon>
                </a>
                <template #list>
                  <DropdownMenu>
                    <DropdownItem name="0">当前页</DropdownItem>
                    <DropdownItem name="1">所有页</DropdownItem>
                  </DropdownMenu>
                </template>
              </Dropdown>
            </div>
          </template>
        </vxe-column>
         <vxe-column field="record_no" title="盘点单号" min-width="150"></vxe-column>
         <vxe-column field="status" title="盘点状态" min-width="80">
          <template #default="{ row }">
            <Tag v-if="row.status === 1" type="border" color="primary">已完成</Tag>
            <Tag v-else type="border" color="error">进行中</Tag>
          </template>
         </vxe-column>
         <vxe-column field="good_stock" title="良品盘盈数量" min-width="120"></vxe-column>
         <vxe-column field="good_inventory_stock" title="良品盘亏数量" min-width="120"></vxe-column>
         <vxe-column field="defective_stock" title="残次品盘盈数量" min-width="120"></vxe-column>
         <vxe-column field="defective_inventory_stock" title="残次品盘亏数量" min-width="120"></vxe-column>
         <vxe-column field="admin_name" title="操作员" min-width="120"></vxe-column>
         <vxe-column field="create_time" title="创建时间" min-width="150"></vxe-column>
         <vxe-column field="remark" title="备注" show-overflow min-width="120"></vxe-column>
         <vxe-column title="操作" width="150" fixed="right">
          <template #default="{ row }">
            <template v-if="row.status == 0">
              <span
                class="text-blue pointer"
                @click="nextEdit(row)"
                >继续盘点</span
              >
              <Divider type="vertical" />
            </template>
            <span
              class="text-blue pointer"
              @click="getInfo(row)"
              >详情</span
            >
            <Divider type="vertical" />
            <span
              class="text-blue pointer"
              @click="setRemark(row)"
              >备注</span
            >
          </template>
        </vxe-column>
      </vxe-table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="params.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="params.limit"
        />
      </div>
    </Card>
    <drawerInfo ref="infoDrawer"></drawerInfo>
  </div>
</template>
<script>
import { getInventoryList, inventorySetMarkApi, exportInventoryApi } from "@/api/inventory.js";
import drawerInfo from "../components/info.vue";
import timeOptions from "@/utils/timeOptions";
import exportExcel from "@/utils/newToExcel.js";
export default {
  name: "getInventoryList",
  data() {
    return {
      options: timeOptions,
      dataTimeVal: [],
      params: {
        page: 1,
        limit: 10,
        record_no: "",
        status: "",
        create_time: "",
      },
      total: 0,
      dataList: [],
      loading: false,
      isAll: 0, // 是否全选 0否 1是
      checkUidList: [],
      isCheckBox: false,
    };
  },
  components: {
    drawerInfo,
  },
  created() {
    this.getList();
  },
  methods: {
    add() {
      this.$router.push({
        path: "/admin/inventory/stockChecks/detail",
      });
    },
    getList() {
      getInventoryList(this.params)
        .then((res) => {
          this.dataList = res.data.list;
          this.total = res.data.count;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    pageChange(index) {
      this.params.page = index;
      this.getList();
    },
    searchList() {
      this.params.page = 1;
      this.getList();
    },
    reset(){
      this.params = {
        page: 1,
        limit: 10,
        record_no: "",
        status: "",
        create_time: "",
      };
      this.dataTimeVal = [];
      this.getList();
    },
    getInfo(row){
      this.$refs.infoDrawer.getInfo(row.id, 1);
    },
    nextEdit(row){
      this.$router.push({
        path: `/admin/inventory/stockChecks/detail/${row.id}`,
      });
    },
    //导出
    async exports() {
      let [th, filekey, data, fileName] = [[], [], [], ""];
      let excelData = {...this.params};
      this.$set(excelData, 'ids', this.checkUidList.join(','));
      this.$set(excelData, 'all', this.isAll);
      excelData.page = 1;
      delete excelData.limit;
      for (let i = 0; i < excelData.page + 1; i++) {
        let lebData = await this.getExcelData(excelData);
        if (!fileName) fileName = lebData.filename;
        if (!filekey.length) {
          filekey = lebData.filekey;
        }
        if (!th.length) th = lebData.header;
        if (lebData.export.length) {
          data = data.concat(lebData.export);
          excelData.page++;
        } else {
          exportExcel(th, filekey, fileName, data);
          return;
        }
      }
    },
    getExcelData(data) {
      return new Promise((resolve) => {
        exportInventoryApi(data).then((res) => resolve(res.data));
      });
    },
    checkboxItem(e) {
      let id = parseInt(e.rowid);
      let index = this.checkUidList.indexOf(id);
      if (index !== -1) {
        this.checkUidList = this.checkUidList.filter((item) => item !== id);
      } else {
        this.checkUidList.push(id);
      }
    },
    checkboxAll() {
      // 获取选中当前值
      let obj2 = this.$refs.xTable.getCheckboxRecords(true);
      // 获取之前选中值
      let obj = this.$refs.xTable.getCheckboxReserveRecords(true);
      if (
        this.isAll == 0 &&
        this.checkUidList.length <= obj.length &&
        !this.isCheckBox
      ) {
        obj = [];
      }
      obj = obj.concat(obj2);
      let ids = [];
      obj.forEach((item) => {
        ids.push(parseInt(item.id));
      });
      this.checkUidList = ids;
      if (!obj2.length) {
        this.isCheckBox = false;
      }
    },
    allPages(e) {
      this.isAll = e;
      console.log(e);
      if (e == 0) {
        this.$refs.xTable.toggleAllCheckboxRow();
        this.checkboxAll();
      } else {
        if (!this.isCheckBox) {
          this.$refs.xTable.setAllCheckboxRow(true);
          this.isCheckBox = true;
          this.isAll = 1;
        } else {
          this.$refs.xTable.setAllCheckboxRow(false);
          this.isCheckBox = false;
          this.isAll = 0;
        }
        this.checkUidList = [];
      }
    },
    setRemark(row){
      this.$modalForm(inventorySetMarkApi(row.id)).then(() => this.getList());
    },
    dataTime(e) {
      this.dataTimeVal = e;
      this.params.create_time = this.dataTimeVal.join("-");
    },
  },
};
</script>
