<?php
class MyBangumi_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $db;
    private $options;
    private $prefix;

    public function insertShort(){
        if(MyBangumi_Plugin::form('insert')->validate()){
            $this->response->goBack();
        }

        //Take out data
        $short = $this->request->from('name', 'orginal', 'text', 'intro', 'image', 'time', 'short2');
        if(!$short['time']){
            $short['time']=time();
        } else {
            $short['time']=strtotime(str_replace(".","-",$short['time']));
        }
		$short['ord'] = $this->db->fetchObject($this->db->select(array('MAX(ord)' => 'maxOrd'))->from($this->prefix.'shorts'))->maxOrd + 1;


        //insert data
        $short['sid'] = $this->db->query($this->db->insert($this->prefix.'shorts')->rows($short));

        //set highlight
        $this->widget('Widget_Notice')->highlight('short-'.$short['sid']);

        //Tips
        $this->Widget('Widget_Notice')->set(_t('番组 %s 已经被增加', $short['name']), NULL, 'success');

        //redirect
		$this->response->redirect(Typecho_Common::url('extending.php?panel=MyBangumi%2Fmanage-shorts.php', $this->options->adminUrl));
    }

    public function updateShort(){
        if(MyBangumi_Plugin::form('insert')->validate()){
            $this->response->goBack();
        }

        //Take out data
        $short = $this->request->from('sid', 'name', 'orginal', 'text', 'intro', 'image', 'time', 'short2');
        if(!$short['time']){
            $short['time']=time();
        } else {
            $short['time']=strtotime(str_replace(".","-",$short['time']));
        }

        //update data
        $short['sid'] = $this->db->query($this->db->update($this->prefix.'shorts')->rows($short)->where('sid = ?', $short['sid']));

        //set highlight
        $this->widget('Widget_Notice')->highlight('short-'.$short['sid']);

        //Tips
        $this->Widget('Widget_Notice')->set(_t('番组 %s 已经被更新', $short['name']), NULL, 'success');

        //redirect
		$this->response->redirect(Typecho_Common::url('extending.php?panel=MyBangumi%2Fmanage-shorts.php', $this->options->adminUrl));
    }

    public function deleteShort(){
        echo 'test';
        $sids = $this->request->filter('int')->getArray('sid');
        $deleteCount = 0;
        if($sids && is_array($sids)){
            foreach($sids as $sid){
                if($this->db->query($this->db->delete($this->prefix.'shorts')->where('sid = ?', $sid))){
                    $deleteCount ++;
                }
            }
        }
        //Tips
        $this->widget('Widget_Notice')->set($deleteCount>0 ? _t('%s链接已经删除', $deleteCount) : _t('没有链接被删除'), NULL, $deleteCount>0 ? 'success' : 'notice');

        //redirect
        $this->response->redirect(Typecho_Common::url('extending.php?panel=MyBangumi%2Fmanage-shorts.php', $this->options->adminUrl));
    }

    public function sortShort()
    {
        $shorts = $this->request->filter('int')->getArray('sid');
        if ($shorts && is_array($shorts)) {
			foreach ($shorts as $sort => $sid) {
                $this->db->query($this->db->update($this->prefix.'shorts')->rows(array('ord' => $sort + 1))->where('sid = ?', $sid));
			}
        }
    }

    public function pageInsert(){
        if(MyBangumi_Plugin::form('insert')->validate()){
            $this->response->goBack();
        }

        //Take out data
        $short = $this->request->from('name', 'orginal', 'text', 'intro', 'image', 'time', 'short2');
        if(!$short['time']){
            $short['time']=time();
        } else {
            $short['time']=strtotime(str_replace(".","-",$short['time']));
        }
		$short['ord'] = $this->db->fetchObject($this->db->select(array('MAX(ord)' => 'maxOrd'))->from($this->prefix.'shorts'))->maxOrd + 1;


        //insert data
        $short['sid'] = $this->db->query($this->db->insert($this->prefix.'shorts')->rows($short));

        //set highlight
        $this->widget('Widget_Notice')->highlight('short-'.$short['sid']);

        //Tips
        $this->Widget('Widget_Notice')->set(_t('番组 %s 已经被增加', $short['name']), NULL, 'success');

        //redirect
		$this->response->redirect(Typecho_Common::url('/', $this->options->adminUrl));
    }


    public function action(){
        $this->db = Typecho_Db::get();
        $this->prefix = $this->db->getPrefix();
        $this->options = Typecho_Widget::widget('Widget_Options');

        $this->on($this->request->is('do=insert'))->insertShort();
        $this->on($this->request->is('do=update'))->updateShort();
        $this->on($this->request->is('do=delete'))->deleteShort();
        $this->on($this->request->is('do=pageinsert'))->pageInsert();

        //$this->response->redirect($this->options->adminUrl('extending.php?panel=MyBangumi%2Fmanage-shorts.php'));
		$this->response->redirect(Typecho_Common::url('extending.php?panel=MyBangumi%2Fmanage-shorts.php', $this->options->adminUrl));
    }
}