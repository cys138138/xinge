<?php

namespace app\admin\controller;

use controller\BasicAdmin;

/**
 * 系统权限管理控制器
 * Class Auth
 * @package app\admin\controller
 */
class CreateCode extends BasicAdmin {

    /**
     * 默认数据模型
     * @var string
     */
    public $table = 'test';

    /**
     * 权限列表
     * @return array|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index() {
        if ($this->request->isGet()) {
            $list = $this->_getTabelLists();
            return $this->fetch('form', ['title' => '代码生成', 'aTabelList' => $this->_getTabelLists()]);
        }

        $nameSpace = (string) $this->request->post('namespace', '');
        $contorllerName = (string) $this->request->post('contorller_name', '');
        $contorllerParent = (string) $this->request->post('contorller_parent', '');
        $tabelName = (string) $this->request->post('table', '');
        if (!$tabelName || !$contorllerName || !$nameSpace) {
            $this->error('缺少参数');
        }
        
        $contorllerFile = APP_PATH . lcfirst($nameSpace) . '/controller/' . ucfirst($contorllerName) . '.php';
        $contorllerFile = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $contorllerFile);
        if (is_file($contorllerFile)) {
            $this->error('控制器已经存在');
        }
        $sControllerString = $this->_dealController($nameSpace, $contorllerName, $tabelName, $contorllerParent);
        if (FALSE === file_put_contents($contorllerFile, $sControllerString)) {
            $this->error('没法生成控制器文件');
        }

        //处理视图文件
        $this->_dealViewTemplate($nameSpace, $contorllerName);

        $this->success('ok','');
    }

    /**
     * 驼峰转中划线
     * @param type $str
     * @return type
     */
    private function humpToTag($str, $tag = '_') {

        $array = array();
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] == strtolower($str[$i])) {
                $array[] = $str[$i];
            } else {
                if ($i > 0) {
                    $array[] = $tag;
                }
                $array[] = strtolower($str[$i]);
            }
        }

        $result = implode('', $array);
        return $result;
    }

    private function _dealViewTemplate($nameSpace, $contorllerName) {
        $viewTemplateBasePath = APP_PATH . lcfirst($nameSpace) . '/view/' . $this->humpToTag($contorllerName);
        if (is_dir($viewTemplateBasePath)) {
            $this->error('模板文件夹已经存在');
        }
        mkdir($viewTemplateBasePath, 0777, true);
        $listFile = APP_PATH . lcfirst($nameSpace) . '/view/' . $this->humpToTag($contorllerName) . '/index.html';
        $listFile = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $listFile);
        if (is_file($listFile)) {
            $this->error('列表已经存在');
        }
        //列表
        $listView = APP_PATH . 'admin/view/create_code/V-L.yink';
        $listView = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $listView);
        $xListViewString = file_get_contents($listView);
        //根据勾选设置列表信息
        $is_sortTr = '';
        $is_sortTh = '';
        //排序
        if((int)$this->request->post('is_sort',0)){
            $is_sortTr = '
            <td class=\'list-table-sort-td\'>
                <input name="_{$vo.id}" value="{$vo.sort}" class="list-sort-input">
            </td>';
            $is_sortTh = '<th class=\'list-table-sort-td\'>
                <button type="submit" class="layui-btn layui-btn-normal layui-btn-xs">排 序</button>
            </th>';
        }
        
        //status
        $statusOpt = '';
        if((int)$this->request->post('is_status',0)){
            $statusOpt = '
                            {if $vo.status eq 1 and auth("$classuri/forbid")}
                <span class="text-explode">|</span>
                <a data-update="{$vo.id}" data-field=\'status\' data-value=\'0\' data-action=\'{:url("$classuri/forbid")}\'>禁用</a>
                {elseif auth("$classuri/resume")}
                <span class="text-explode">|</span>
                <a data-update="{$vo.id}" data-field=\'status\' data-value=\'1\' data-action=\'{:url("$classuri/resume")}\'>启用</a>
                {/if}';
        }
        //编辑
        $is_editor = '';
        if((int)$this->request->post('is_editor',0)){
            $is_editor = '
                {if auth("$classuri/edit")}
                <span class="text-explode">|</span>
                <a data-title="编辑" data-open=\'{:url("$classuri/edit")}?id={$vo.id}\'>编辑</a>
                {/if}';
        }
        //删除
        $is_deleted = '';
        $deletedBatch = '';
        if((int)$this->request->post('is_deleted',0)){
            $is_deleted = '
                {if auth("$classuri/del")}
                <span class="text-explode">|</span>
                <a data-update="{$vo.id}" data-field=\'delete\' data-action=\'{:url("$classuri/del")}\'>删除</a>
                {/if}';
            $deletedBatch = '{if auth("$classuri/del")}
            <button data-update data-field=\'delete\' data-action=\'{:url("$classuri/del")}\' class=\'layui-btn layui-btn-sm layui-btn-danger\'>批量删除</button>
            {/if}';
        }
        //添加
        $is_add = '';
        if((int)$this->request->post('is_add',0)){
            $is_add = '
            {if auth("$classuri/add")}
            <button data-open=\'{:url("$classuri/add")}\' data-title="添加" class=\'layui-btn layui-btn-sm\'>添加</button>
            {/if}';
        }
        
        $xListViewString = str_replace(
                array('{__SEARCH__}', '{__TH__}', '{__TR__}','{__SORTTH__}','{__SORTTR__}','{__STATUSOPT__}','{__EDITOR__}','{__DELETED__}','{__DELETD_BATCH__}','{__ADD_OPT__}'), 
                array($this->_buildTemplateSearch(), $this->_buildListTh(), $this->_buildListTr(),$is_sortTh,$is_sortTr,$statusOpt,$is_editor,$is_deleted,$deletedBatch,$is_add
                ), $xListViewString);
        
        if (FALSE === file_put_contents($listFile, $xListViewString)) {
            $this->error('没法生成控制器文件');
        }
        $formFile = APP_PATH . lcfirst($nameSpace) . '/view/' . $this->humpToTag($contorllerName) . '/form.html';
        $formFile = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $formFile);
        if (is_file($formFile)) {
            $this->error('表单视图已经存在');
        }
        //表单视图
        $formView = APP_PATH . 'admin/view/create_code/V-F.yink';
        $formView = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $formView);
        $xformViewString = file_get_contents($formView);
        $xformViewString = str_replace(array('{__FEILD_LIST__}'), array($this->_buildTemplateForm()), $xformViewString);
        if (FALSE === file_put_contents($formFile, $xformViewString)) {
            $this->error('没法生成控制器文件');
        }
        return true;
    }

    private function _buildTemplateForm() {
        //日期选择字段
        $aDateSelect = isset($_POST['date']) ? $_POST['date'] : [];
        //图片字段
        $aImageSelect = isset($_POST['image']) ? $_POST['image'] : [];
        //编辑器
        $aEditor = isset($_POST['ed']) ? $_POST['ed'] : [];
        //别名字段
        $aAliasList = isset($_POST['alias']) ? $_POST['alias'] : [];
        //检索字段
        $aInputField = isset($_POST['input']) ? $_POST['input'] : [];
        //下拉框字段
        $aSelectList = isset($_POST['select']) ? $_POST['select'] : [];
        
        $tabelName = $this->request->post('table', '');
        $aTableFeildList = $this->_getTabelFeildAttrByTabelName($tabelName);
        $xHtml = '';
        foreach ($aTableFeildList as $aItem) {
            //只处理表单字段
            if (!isset($aInputField[$aItem['Field']])) {
                continue;
            }
            $fieldName = isset($aAliasList[$aItem['Field']]) ? $aAliasList[$aItem['Field']] : $aItem['Field'];
            $field = $aItem['Field'];
            if (isset($aDateSelect[$aItem['Field']])) {
                $xHtml .= '    <div class="form-group">
                    <label class="col-sm-2 control-label">
                        ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
                    </label>
                    <div class="col-sm-8">
                        <input name="' . $field . '" id="' . $field . '" value="{$vo.' . $field . '|default=\'\'}" placeholder="请选择' . $fieldName . '" class="layui-input">
                        <p class="help-block">' . $fieldName . '</p>
                    </div>
                    <script>window.laydate.render({range: false, elem: "#' . $field . '"}); </script>
                </div>
            <div class="hr-line-dashed"></div>';
                continue;
            }
            if (isset($aImageSelect[$aItem['Field']])) {
                $xHtml .= '     <div class="form-group">
        <label class="col-sm-2 control-label">
            ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
        </label>
        <div class="col-sm-8">
            <img data-tips-image style="height:auto;max-height:32px;min-width:32px" src=\'{$vo.' . $field . '|default=""}\'/>
            <input type="hidden" name="' . $field . '" onchange="$(this).prev(\'img\').attr(\'src\', this.value)" value=\'{$vo.' . $field . '|default="http://static.xdh-syy.com/theme/default/img/image.png"}\' class="layui-input">
            <a class="btn btn-link" data-file="one" data-uptype="local" data-type="ico,png" data-field="' . $field . '">上传图片</a>
            <p class="help-block">建议尺寸为</p>
        </div>
    </div>
            <div class="hr-line-dashed"></div>';
                continue;
            }
            //编辑器
            if (isset($aEditor[$aItem['Field']])) {
                $xHtml .= '        
        <div class="form-group">
            <label class="col-sm-2 control-label">
                ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
            </label>
            <div class="col-sm-8">
                <textarea name=\'' . $field . '\'>{$vo.' . $field . '|default=""}</textarea>                
                <p class="help-block">' . $fieldName . '</p>
            </div>
            <script>require([\'ckeditor\'], function () {
                var editor = window.createEditor(\'[name="' . $field . '"]\'); 
            });</script>
        </div>
            <div class="hr-line-dashed"></div>';
                continue;
            }
        //将status下拉处理成 单选    
        if (isset($aSelectList[$aItem['Field']]) && $aItem['Field'] == 'status') {
                $xHtml .= '
            <div class="form-group">
            <label class="col-sm-2 control-label">
                ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
            </label>
            <div class="col-sm-8">
                <label class="think-radio">
                    <input type="radio" name="' . $field . '" value="1" {if !isset($vo[\'' . $field . '\'])}checked{/if} {if isset($vo[\'' . $field . '\']) and $vo[\'' . $field . '\'] eq 1}checked{/if} lay-ignore="">
                    启用
                </label>
                <label class="think-radio">
                    <input type="radio" name="' . $field . '" value="0" {if isset($vo[\'' . $field . '\']) and $vo[\'' . $field . '\'] eq 1}checked{/if} lay-ignore="">
                    禁用
                </label>
                <p class="help-block">' . $fieldName . '</p>
            </div>
        </div>
        <div class="hr-line-dashed"></div>';
                continue;
        }
        
        //非status下拉情况(关联关系)
        if (isset($aSelectList[$aItem['Field']])) {
            //编辑器字段
            $key = $aItem['Field'];
            $aRelationList = isset($_POST['relation']) ? $_POST['relation'] : [];
             //判断关联条件
            if (isset($aRelationList[$key])) {
                    //判断是否选择了表和字段
                    $table = $this->request->post('relation_table_' . $key, false);
                    $field1 = $this->request->post('relation_field_' . $key, false);
                    $showField = $this->request->post('relation_field_show_' . $key, false);
                    if ($showField && $table && $field1) {
                        $dataName = 'aData' . ucfirst($key) . ucfirst($field1) . 'List';
                        $xHtml .= '
                        <div class="form-group">
                        <label class="col-sm-2 control-label">
                            ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
                        </label>
		<div class="col-sm-8">
                    <select name=\'' . $key . '\' class=\'layui-select full-width\' lay-ignore>
                            {foreach $' . $dataName . ' as $aItem}
                            {eq name=\'$aItem.' . $field1 . '\' value=\'$vo.' . $key . '|default=0\'}
                            <option selected value=\'{$aItem.' . $field1 . '}\'>{$aItem.' . $showField . '}</option>
                            {else}
                            <option value=\'{$aItem.' . $field1 . '}\'>{$aItem.' . $showField . '}</option>
                            {/eq}
                            {/foreach}
                    </select>
            </div>
                    </div>
                    <div class="hr-line-dashed"></div>';
                    }
                    continue;
                }


                $xHtml .= '
                        <div class="form-group">
                        <label class="col-sm-2 control-label">
                            ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
                        </label>
                        <div class="col-sm-8">
                            <div class="layui-input-block">
                            <select name=\'' . $field . '\' class=\'layui-select full-width\' lay-ignore><option value=""></option>
                            </select>
                            </div>
                        <p class="help-block">' . $fieldName . '</p>
                        </div>
                    </div>
                    <div class="hr-line-dashed"></div>';
                continue;
        }
            $xHtml .= '<div class="form-group">
            <label class="col-sm-2 control-label">
                ' . $fieldName . '<br><span class="nowrap color-desc">' . $fieldName . '</span>
            </label>
            <div class="col-sm-8">
                <input name="' . $field . '" required="required" title="请输入' . $fieldName . '" placeholder="请输入' . $fieldName . '" value=\'{$vo.' . $field . '|default=""}\' class="layui-input">
                <p class="help-block">' . $fieldName . '</p>
            </div>
        </div>
        <div class="hr-line-dashed"></div>';
        }
        return $xHtml;
    }

    private function _buildListTh() {
        //别名字段
        $aAliasList = isset($_POST['alias']) ? $_POST['alias'] : [];
        //检索字段
        $aFromList = isset($_POST['list']) ? $_POST['list'] : [];
        $tabelName = $this->request->post('table', '');
        $aTableFeildList = $this->_getTabelFeildAttrByTabelName($tabelName);
        $xHtml = '';
        foreach ($aTableFeildList as $aItem) {
            //只处理勾选检索的情况
            if (!isset($aFromList[$aItem['Field']])) {
                continue;
            }
            $fieldName = isset($aAliasList[$aItem['Field']]) ? $aAliasList[$aItem['Field']] : $aItem['Field'];
            $xHtml .= '<th class=\'text-left\'>' . $fieldName . '</th>
            ';
        }
        return $xHtml;
    }

    private function _buildListTr() {
        //日期选择字段
        $aDateSelect = isset($_POST['date']) ? $_POST['date'] : [];
        //图片字段
        $aImageSelect = isset($_POST['image']) ? $_POST['image'] : [];
        //别名字段
        $aAliasList = isset($_POST['alias']) ? $_POST['alias'] : [];
        //检索字段
        $aFromList = isset($_POST['list']) ? $_POST['list'] : [];
        $tabelName = $this->request->post('table', '');
        $aTableFeildList = $this->_getTabelFeildAttrByTabelName($tabelName);
        $xHtml = '';
        foreach ($aTableFeildList as $aItem) {
            //只处理勾选检索的情况
            if (!isset($aFromList[$aItem['Field']])) {
                continue;
            }
            $fieldName = isset($aAliasList[$aItem['Field']]) ? $aAliasList[$aItem['Field']] : $aItem['Field'];
            $field = $aItem['Field'];
            if (isset($aDateSelect[$aItem['Field']])) {
                $xHtml .= ' <td class=\'text-left nowrap\'>
                {$vo.' . $field . '|format_datetime|default="<span class=\'color-desc\'>--</span>"|raw}
            </td>';
                continue;
            }
            if (isset($aImageSelect[$aItem['Field']])) {
                $xHtml .= ' <td class=\'text-left nowrap\'>
                <img data-tips-image=\'{$vo.' . $field . '}\'  class="list-table-image" width="100px" src=\'{$vo.' . $field . '}\'></div>
            </td>';
                continue;
            }
            $status = ['status' => 1];
            if ((int)$this->request->post('is_status',0) && isset($status[$aItem['Field']])) {                
                $xHtml .= ' <td class=\'text-center\'>
                {if $vo.status eq 0}<span>已禁用</span>{elseif $vo.status eq 1}<span class="color-green">使用中</span>{/if}
            </td>';
                continue;
            }
            $xHtml .= '<td class=\'text-left\'>{$vo.' . $field . '}</td>
            ';
        }
        return $xHtml;
    }

    /**
     * 模板列表检索生成
     */
    private function _buildTemplateSearch() {
        //编辑器字段
        $aEditorList = isset($_POST['ed']) ? $_POST['ed'] : [];
        //日期选择字段
        $aDateSelect = isset($_POST['date']) ? $_POST['date'] : [];
        //图片字段
        $aImageSelect = isset($_POST['image']) ? $_POST['image'] : [];
        //下拉框字段
        $aSelectList = isset($_POST['select']) ? $_POST['select'] : [];
        //别名字段
        $aAliasList = isset($_POST['alias']) ? $_POST['alias'] : [];
        //检索字段
        $aSearchList = isset($_POST['search']) ? $_POST['search'] : [];
        $tabelName = $this->request->post('table', '');
        $aTableFeildList = $this->_getTabelFeildAttrByTabelName($tabelName);
        $xHtml = '';
        foreach ($aTableFeildList as $aItem) {
            //只处理勾选检索的情况
            if (!isset($aSearchList[$aItem['Field']])) {
                continue;
            }
            $fieldName = isset($aAliasList[$aItem['Field']]) ? $aAliasList[$aItem['Field']] : $aItem['Field'];
            $field = $aItem['Field'];
            
            $aRelationList = isset($_POST['relation']) ? $_POST['relation'] : [];
            
            //处理下拉情况
            if (isset($aSelectList[$aItem['Field']]) && !isset($aRelationList[$aItem['Field']])) {
                $xHtml .= '
            <div class="layui-form-item layui-inline">
                    <label class="layui-form-label">' . $fieldName . '</label>
                    <div class="layui-input-inline">
                        <select name="' . $field . '" class="layui-select" lay-search="">
                            <option value=""> - 请选择 -</option>
                        </select>
                    </div>
                </div>';
                continue;
            }
            //处理日期
            if (isset($aDateSelect[$aItem['Field']])) {
                $xHtml .= '
            <div class="layui-form-item layui-inline">
                        <label class="layui-form-label">' . $fieldName . '</label>
                        <div class="layui-input-inline"><input name="' . $field . '" id="' . $field . '" value="{$Think.get.' . $field . '|default=\'\'}" placeholder="请选择' . $fieldName . '" class="layui-input">
                        </div>
                    <script>window.laydate.render({range: true, elem: "#' . $field . '"}); </script>
                    </div>
                ';
                continue;
            }
            $xHtml .= '
            <div class="layui-form-item layui-inline">
                <label class="layui-form-label">' . $fieldName . '</label>
                <div class="layui-input-inline">
                    <input name="' . $field . '" value="{$Think.get.' . $field . '|default=\'\'}" placeholder="请输入' . $fieldName . '" class="layui-input">
                </div>
            </div>';
        }
        return $xHtml;
    }

    private function _dealController($namespace, $contorllerName, $tabelName, $extendClass) {
        $contorllerFile = APP_PATH . lcfirst($namespace) . '/view/create_code/C.yink';
        $contorllerFile = str_replace(array('/', '\\'), array(DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR), $contorllerFile);
        $xControllerString = file_get_contents($contorllerFile);
        
        $add = '';
        if((int)$this->request->post('is_add',0)){
            $add = '
            /**
             * 添加
             */
            public function add()
            {
               $this->title = \'添加\';
               return parent::add();
            }';
        }
        $is_editor = '';
        if((int)$this->request->post('is_editor',0)){
            $is_editor = '
            /**
            * 编辑
            */
           public function edit()
           {
               $this->title = \'编辑\';
               return parent::edit();
           }';
        }
        
        $is_deleted = '';
        if((int)$this->request->post('is_deleted',0)){
            $is_deleted = '
            /**
             * 删除
             */
            public function del()
            {
                return parent::del();
            }
        ';
        }
        
        $is_status= '';
        if((int)$this->request->post('is_status',0)){
            $is_status = '
 
                /**
                 * 禁用
                 */
                public function forbid()
                {
                   return parent::forbid();
                }

                /**
                 * 恢复
                 */
                public function resume()
                {
                   return parent::resume();
                }';
        }        
        //控制检索条件
        $aSearchList = isset($_POST['search']) ? $_POST['search'] : [];
        $tabelName = $this->request->post('table', '');
        //日期选择字段
        $aDateSelect = isset($_POST['date']) ? $_POST['date'] : [];        
        //编辑器字段
        $aEditorList = isset($_POST['ed']) ? $_POST['ed'] : [];
        
        //编辑器字段
        $aRelationList = isset($_POST['relation']) ? $_POST['relation'] : [];
        $aTableFeildList = $this->_getTabelFeildAttrByTabelName($tabelName);
        $xCondition = '';
        if(count($aSearchList)>0){
            $xCondition .= '
            list($get, $db) = [$this->request->get(), Db::name($this->table)];';
        }
        //编辑器处理
        $editor = '';
        //选择关联关系
        $relation = '';
        foreach ($aTableFeildList as $aItem) {            
            $key = $aItem['Field'];
            //编辑器            
            if(isset($aEditorList[$key])){
                $editor = '
                    if (isset($param[\''. $key .'\'])) {
                        $param[\''. $key .'\'] = htmlspecialchars_decode($param[\''. $key .'\']);
                    }';
            }
            
            //判断关联条件
            if (isset($aRelationList[$key])) {
                //判断是否选择了表和字段
                $table = $this->request->post('relation_table_' . $key, false);
                $field = $this->request->post('relation_field_' . $key, false);
                $showField = $this->request->post('relation_field_show_' . $key, false);
                if ($showField && $table && $field) {
                    $dataName = 'aData'. ucfirst($key). ucfirst($field).'List';
                    $relation .='
                        if(isset($param[\''. $key .'\'])){
                            $'. $dataName .' = Db::name(\''. $table .'\')->select();
                            $this->assign(\''.$dataName.'\', $'. $dataName .');
                        }';
                }                
            }
            
            //只处理勾选检索的情况
            if (!isset($aSearchList[$aItem['Field']])) {
                continue;
            }
            
            if (isset($aDateSelect[$key])) {
                //字符串类型的
                if (false !== strpos($aItem['Type'], 'datetime') || false !== strpos($aItem['Type'], 'timestamp')) {
                    $xCondition .= '
                    if (isset($get[\'' . $key . '\']) && $get[\'' . $key . '\'] !== \'\') {
                        list($start, $end) = explode(\' - \', $get[\'' . $key . '\']);
                        $db->whereBetween(\'' . $key . '\', ["{$start} 00:00:00", "{$end} 23:59:59"]);
                    }';
                    continue;
                }
                //时间戳整型处理
                $xCondition .= '
                    if (isset($get[\'' . $key . '\']) && $get[\'' . $key . '\'] !== \'\') {
                        list($start, $end) = explode(\' - \', $get[\'' . $key . '\']);
                        $db->whereBetween(\'' . $key . '\', ["{strtotime($start)}", "{strtotime($end)}"]);
                }';
                continue;
            }
            //判断类型(string)
            if (false !== strpos($aItem['Type'], 'char')) {
                $xCondition .= '
            (isset($get["' . $key . '"]) && $get["' . $key . '"] !== \'\') && $db->whereLike("' . $key . '", "%{$get["' . $key . '"]}%");';
            }
        }
        if($xCondition != ''){
            $xCondition .= '
            return parent::_list($db);';
        }else{
            $xCondition .= '
            return parent::index();';
        }
        
        return str_replace(array('{__NAMESPACE__}', '{__CONTROLLER_NAMW__}', '{__TABLE_NAME__}', '{__EXTEND_NAME__}','{__ADDFUN__}','{__EDITORFUN__}','{__STATUSFUN__}','{__DELFUN__}','{__CONDITION__}','{__EDITORFORMATTER__}','{__RELATIONSELECT__}'), 
                array(lcfirst($namespace), ucfirst($contorllerName), $tabelName, $extendClass,$add,$is_editor,$is_status,$is_deleted,$xCondition,$editor,$relation)
                , $xControllerString);
    }

    public function field() {
        $tableName = (string) $this->request->get('table_name', '');
        $aTabel = $this->_getTabelFeildAttrByTabelName($tableName);
        return $this->success('获取成功', null, $aTabel);
    }

    private function _getTabelFeildAttrByTabelName($tabelName) {
        return db()->query('SHOW FULL COLUMNS FROM `' . $tabelName . '`');
    }

    /**
     * 获取表
     * @return type
     */
    private function _getTabelLists() {
        $aTabelList = db()->query('show tables');
        $tabels = [];
        foreach ($aTabelList as $tabel) {
            foreach ($tabel as $v) {
                $tabels[] = $v;
            }
        }
        return $tabels;
    }

}
