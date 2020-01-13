<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 一个用来显示追番和短评的插件
 * 
 * @package MyBangumi
 * @author Panedioic
 * @version 0.1
 * @link http://blog.pppane.com
 * 
 * 感谢 侍风 (http://shifeng-kaze.cn/) 的番剧短评源码和 寒泥 (http://www.imhan.com) 的友链插件代码参考
 * 
 * 注：因为觉得新搞一搞独立页面麻烦所以直接把内容都输出到普通的页面然后用js搞成想要的效果_(:з」∠)_     原来的anime.php也是可以使用的。。
 * 使用时请在文章加入<bangumi></bangumi>标签。
 * 
 * Todolist:
 * 1.插件的更新检测
 * 2.加入bgm.tv api支持
 * 3.加入与看板娘的互动
 */
class MyBangumi_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        $info = MyBangumi_Plugin::PluginInstall();

        Helper::addPanel(3, 'MyBangumi/manage-shorts.php', '短评', '管理短评', 'administrator');
        Helper::addAction('shorts-edit', 'MyBangumi_Action');

        Typecho_Plugin::factory('admin/menu.php')->navBar = array('MyBangumi_Plugin', 'render');

        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('MyBangumi_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('MyBangumi_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Comments')->contentEx = array('MyBangumi_Plugin', 'parse');

        return $info;
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        Helper::removeAction('shorts-edit');
        Helper::removePanel(3, 'MyBangumi/manage-shorts.php');
    }
    
    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 分类名称 */
        $name = new Typecho_Widget_Helper_Form_Element_Text('word', NULL, 'Hello World', _t('说点什么'));
        $form->addInput($name);
    }
    
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function PluginInstall(){
        $installDb = Typecho_Db::get();
        $type = explode('_', $installDb->getAdapterName());
        $type = array_pop($type);
        //暂时只支持Mysql数据库
        if('Mysql' != $type){
            return '暂时只支持安装Mysql数据库，插件安装失败！您也可以尝试手动配置数据库。';
        }
        $prefix = $installDb->getPrefix();
        $scripts = file_get_contents('usr/plugins/MyBangumi/'.$type.'.sql');
        $scripts = str_replace('typecho_', $prefix, $scripts);
        $scripts = str_replace('%charset%', 'utf8', $scripts);
        $scripts = explode(';', $scripts);
        try{
            foreach ($scripts as $script){
                $script = trim($script);
                if($script){
                    $installDb->query($script, Typecho_Db::WRITE);
                }
            }
        }catch(Typecho_Db_Exception $e){
            $code = $e->getCode();
            if(1050 == $code){
                try {
					$script = 'SELECT `sid`, `name`, `orginal`, `text`, `intro`, `image`, `time`, `short2`, `order` from `' . $prefix . 'shorts`';
					$installDb->query($script, Typecho_Db::READ);
					return '检测到数据表，插件启用成功';					
				} catch (Typecho_Db_Exception $e) {
					throw new Typecho_Plugin_Exception('数据表检测失败，插件启用失败。错误号：'.$code);
				}
			} else {
				throw new Typecho_Plugin_Exception('数据表建立失败，插件启用失败。错误号：'.$code);
			}
        }
    }
    
    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render()
    {
        echo '<span class="message success">'
            . htmlspecialchars(Typecho_Widget::widget('Widget_Options')->plugin('MyBangumi')->word)
            . '</span>';
    }

    public static function form($action = NULL)
    {
        /**  构建表格 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/shorts-edit', $options->index), Typecho_Widget_Helper_Form::POST_METHOD);

        //name
        $name = new Typecho_Widget_Helper_Form_Element_Text('name', NULL, NULL, _t('番剧标题'));
        $form->addInput($name);

        //orginal
        $orginal = new Typecho_Widget_Helper_Form_Element_Text('orginal', NULL, NULL, _t('原名'));
        $form->addInput($orginal);

        //text
        $text = new Typecho_Widget_Helper_Form_Element_Textarea('text', NULL, NULL, _t('短评'));
        $form -> addInput($text);

        //intro
        $intro = new Typecho_Widget_Helper_Form_Element_Textarea('text', NULL, NULL, _t('简介'));
        $form->addInput($intro);

        //image
        $image = new Typecho_Widget_Helper_Form_Element_Text('image', NULL, NULL, _t('图片链接'));
        $form->addInput($image);

        //time
        $time = new Typecho_Widget_Helper_Form_Element_Text('time', NULL, NULL, _t('时间'));
        $form->addInput($time);

        //short2
        $short2 = new Typecho_Widget_Helper_Form_Element_Textarea('short2', NULL, NULL, _t('插件扩展'));
        $form->addInput($short2);

        //do(action)
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        //sid(primary key)
        $sid = new Typecho_Widget_Helper_Form_Element_Hidden('sid');
        $form->addInput($sid);

        //button
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        $request = Typecho_Request::getInstance();

        if(isset($request->sid) && 'insert' != $action){
            //update mode
            $db = Typecho_Db::get();
            $prefix = $db->getPrefix();
            $short = $db->fetchRow($db->select()->from($prefix.'shorts')->where('sid = ?', $request->sid));
            if(!$short){
                throw new Typecho_Widget_Exception(_t('Short does not exist!'), 404);
            }

            $name->value($short['name']);
            $orginal->value($short['orginal']);
            $text->value($short['text']);
            $intro->value($short['intro']);
            $image->value($short['image']);
            //$time->value($short['time']);
            $time->value(date('Y-m-d',$short['time']));
            $short2->value($short['short2']);
            $do->value('update');
            $sid->value($short['sid']);
            $submit->value(_t('编辑短评'));
            $_action = 'update';
        } else {
            $time->value(date('Y-m-d'));
            $do->value('insert');
            $submit->value(_t('增加短评'));
            $_action = 'insert';
        }

        //make rules for form
        if('insert' == $action || 'update' == $action){
            $name->addRule('required', _t('必须填写番剧标题'));
            $image->addRule('url', _t('不是一个合法的图片地址'));
        }
        if('update' == $action){
            $sid->addRule('required', _t('短评主键不存在'));
            $sid->addRule(array(new MyBangumi_Plugin, 'ShortExists'), _t('短评不存在'));
        }
        return $form;
    }

    public static function ShortExists($sid){
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $short = $db->fetchRow($db->select()->from($prefix.'shorts')->where('sid = ?', $sid)->limit(1));
        return $short ? true : false;
    }

    //因为觉得新搞一搞独立页面麻烦所以直接把内容都输出到普通的页面然后用js搞成想要的效果_(:з」∠)_     原来的anime.php也是可以使用的。。
    public static function output_str($repaint = true){
        $options = Typecho_Widget::Widget('Widget_Options');
        if(!isset($options->plugins['activated']['MyBangumi'])){
            return '插件未激活!';
        }
        
        if($repaint){
            $str_js3 = '<script>if(typeof once == "undefined"){
            document.getElementById(\'morphing-content\').remove();
            var source = document.getElementsByTagName(\'bangumi_out\')[0];
            var to = document.getElementsByTagName(\'main\')[0];
            to.innerHTML = source.innerHTML;}
            once = 1;</script>
            ';
            $str = $str_js3;
        }

        $str .= '<bangumi_out>';

        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $shorts = $db->fetchAll($db->select()->from($prefix.'shorts')->order($prefix.'shorts.ord', Typecho_Db::SORT_DESC));

        if($repaint){
            $str_header = file_get_contents('usr/plugins/MyBangumi/anime_header.html');
            $str_header = str_replace('%d', count($shorts), $str_header);
            $str .= $str_header;
        }

        $str .= str_replace('{{href}}', '/usr/plugins/MyBangumi/css/shifeng.css', '<link rel="stylesheet" href="{{href}}" type="text/css" />        ');

        if(Typecho_Widget::widget('Widget_User')->hasLogin()){
            $action_url = Typecho_Common::url('/action/shorts-edit?do=pageinsert', $options->index);
            $str_manage = file_get_contents('usr/plugins/MyBangumi/anime_manage.html');
            $str_manage = str_replace('{{action}}', $action_url, $str_manage);
            $str .= $str_manage;
        }

        //animeline
        $str .= str_replace('{{href}}', '/usr/plugins/MyBangumi/css/animaline.css', '<link rel="stylesheet" href="{{href}}" type="text/css" />        ');
        $str .= str_replace('{{href}}', '/usr/plugins/MyBangumi/js/modernizr.js', '<script type="text/javascript" src="{{href}}"></script>        ');

        $str .= "    <section id=\"cd-timeline\" class=\"cd-container\">\n";

        //$res = $this->db->fetchAll($this->db->select('table.shorts.name', 'table.shorts.orginal', 'table.shorts.text', 'table.shorts.intro', 'table.shorts.image', 'table.shorts.time')->from('table.shorts')->order('time', Typecho_Db::SORT_DESC));
        $i = 0;
        $pic = ['nagi','shana','ruizu','taiga'];
        $month = ['Jan','Feb','Mar','Apr', 'May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        foreach ($shorts as $text) {
            $date = date('Y.m.d',$text['time']);
            $time = explode(".",$date);
            if(count($time) === 3){
                if(substr($time[1],0,1) == 0){
                    $time[1] = substr($time[1],1,1);
                }
                if(substr($time[2],0,1) == 0){
                    $time[2] = substr($time[2],1,1);
                }
                $mon = (int)$time[1];
                if($mon>0&&$mon<13){
                    $time = $month[$mon-1].' '.$time[2].' , '.$time[0].'';
                }
            }else{
                $time = $text['time'];
            }
            $temple = '<div class="cd-timeline-block">
        <div class="cd-timeline-img cd-{PIC}"></div><!-- cd-timeline-img -->

        <div class="cd-timeline-content">
            <div class="sf-anima-box" id="sf-flip">
                <div class="sf-anima-img-box"><img src="{IMAGE}" class="sf-anima-img"></div>
                <div class="sf-anima-intro-box">
                    <b>{NAME}</b><br>
                    <i class="sf-anima-name-jp">{ORGINAL}</i>
                    <p class="sf-anima-br">
                    <p class="sf-anima-intro">{INTRO}</p>
                </div>
                <div class="sf-anima-comment" id="sf-panel">
                    {TEXT}<br>
                </div>
            </div>
            <span class="cd-date">{TIME}</span>
        </div> <!-- cd-timeline-content -->
    </div> <!-- cd-timeline-block -->
    ';
            $temple = str_replace('{PIC}',$pic[$i],$temple);
            $temple = str_replace('{IMAGE}',$text['image'],$temple);
            $temple = str_replace('{NAME}',$text['name'],$temple);
            $temple = str_replace('{ORGINAL}',$text['orginal'],$temple);
            $temple = str_replace('{INTRO}',$text['intro'],$temple);
            $temple = str_replace('{TEXT}',$text['text'],$temple);
            $temple = str_replace('{TIME}',$time,$temple);
            $i = ($i+1) % 4;
            $str .= $temple;
        }

        $str .= "</section> <!-- cd-timeline -->\n";
        
        $str_js2 = file_get_contents('usr/plugins/MyBangumi/anime_js2.html');
        $str .= $str_js2;
        
        $str .= "\n</bangumi_out>";

        return $str;
    }

    /**
     * 解析
     * 
     * @access public
     * @param array $matches 解析值
     * @return string
     */
    public static function parseCallBack($matches)
    {
        $db = Typecho_Db::get();
        $pattern = $matches[3];
        $links_num = $matches[1];
        $sort = $matches[2];
        return MyBangumi_Plugin::output_str();//MyBangumi_Plugin::output_str($pattern, $links_num, $sort);
    }

    public static function parse($text, $widget, $lastResult)
    {
        $text = empty($lastResult) ? $text : $lastResult;

        if ($widget instanceof Widget_Archive || $widget instanceof Widget_Abstract_Comments){
            return preg_replace_callback("/<bangumi\s*(\d*)\s*(\w*)>\s*(.*?)\s*<\/bangumi>/is", array('MyBangumi_Plugin', 'parseCallBack'), $text);
        } else {
            return $text;
        }
    }
}
