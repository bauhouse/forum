<?php

	include_once(TOOLKIT . '/class.entrymanager.php');
	include_once('lib/class.discussion.php');
	
	Class extension_forum extends Extension{

		public $Discussion;

		public function about(){
			return array('name' => 'Forum',
						 'version' => '2.0 Alpha',
						 'release-date' => '2008-04-12',
						 'author' => array('name' => 'Symphony Team',
										   'website' => 'http://www.symphony21.com',
										   'email' => 'team@symphony21.com')
				 		);
		}

		function __construct($args){
			parent::__construct($args);

			$this->Discussion = new Discussion($this->_Parent);

		}

		public function uninstall(){
			Symphony::Configuration()->remove('forum');			
			$this->_Parent->saveConfig();
			Symphony::Database()->query("DROP TABLE `tbl_forum_read_discussions`");
		}

		public function install(){

			return Symphony::Database()->query(
				"CREATE TABLE `tbl_forum_read_discussions` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`member_id` int(11) unsigned NOT NULL,
					`discussion_id` int(11) unsigned NOT NULL,
					`last_viewed` int(11) unsigned NOT NULL,
					`comments` int(11) unsigned NOT NULL,
					PRIMARY KEY  (`id`),
					KEY `member_id` (`member_id`,`discussion_id`)
				)");

		}

		public function getSubscribedDelegates(){
			return array(

				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),

			);
		}

		public function appendPreferences($context){

			include_once(TOOLKIT . '/class.sectionmanager.php');
			$sectionManager = new SectionManager($context['parent']);
		    $sections = $sectionManager->fetch(NULL, 'ASC', 'name');
			$field_groups = array();

			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');

			$group->appendChild(new XMLElement('legend', 'Forum'));

			$p = new XMLElement('p', 'This field is the section link that ties comments to discussions.');
			$p->setAttribute('class', 'help');
			$group->appendChild($p);
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));

			$label = Widget::Label('Discussion Section');
			
			$options = array();

			foreach($sections as $s){
				$options[] = array($s->get('id'), (Symphony::Configuration()->get('discussion-section', 'forum') == $s->get('id')), $s->get('name'));
			}

			$label->appendChild(Widget::Select('settings[forum][discussion-section]', $options));
			$div->appendChild($label);


			$label = Widget::Label('Comment Section');
			
			$options = array();

			foreach($sections as $s){
				$options[] = array($s->get('id'), (Symphony::Configuration()->get('comment-section', 'forum') == $s->get('id')), $s->get('name'));
			}

			$label->appendChild(Widget::Select('settings[forum][comment-section]', $options));
			$div->appendChild($label);
			
			$group->appendChild($div);

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$div->appendChild($this->createFieldSelector('Discussion Member Link', 'member-link-field', 'selectbox_link', $sections));
			$div->appendChild($this->createFieldSelector('Discussion Last Post', 'discussion-last-post-field', 'selectbox_link', $sections));
			$group->appendChild($div);
			
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$div->appendChild($this->createFieldSelector('Discussion Last Active (Date)', 'discussion-last-active-field', 'date', $sections));
			$div->appendChild($this->createFieldSelector('Earliest Unread Discussion Cutoff (Date)', 'unread-cutoff-field', 'date', $sections));
			$group->appendChild($div);
						

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$div->appendChild($this->createFieldSelector('Pinned Flag', 'pinned-field', 'checkbox', $sections));			
			$div->appendChild($this->createFieldSelector('Locked Flag', 'locked-field', 'checkbox', $sections));
			$group->appendChild($div);
				
			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$div->appendChild($this->createFieldSelector('Comment Discussion Link', 'comment-discussion-link-field', 'selectbox_link', $sections));
			$div->appendChild($this->createFieldSelector('Comment Member Link', 'comment-member-link-field', 'selectbox_link', $sections));
			$group->appendChild($div);			

			$div = new XMLElement('div', NULL, array('class' => 'group'));
			$div->appendChild($this->createFieldSelector('Comment Creation Date', 'comment-creation-date-field', 'date', $sections));
			$div->appendChild($this->createFieldSelector('Comment Text Field', 'comment-field', 'textarea', $sections));
			$group->appendChild($div);

			$context['wrapper']->appendChild($group);

		}
		
		public function createFieldSelector($title, $handle, $type, $sections){
			$label = Widget::Label($title);
			
			if(is_array($sections) && !empty($sections))
				foreach($sections as $section) $field_groups[$section->get('id')] = array('fields' => $section->fetchFields($type), 'section' => $section);

			$options = array();

			foreach($field_groups as $g){

				if(!is_array($g['fields'])) continue;

				$fields = array();
				foreach($g['fields'] as $f){
					$fields[] = array($f->get('id'), (Symphony::Configuration()->get($handle, 'forum') == $f->get('id')), $f->get('label'));
				}

				if(is_array($fields) && !empty($fields)) $options[] = array('label' => $g['section']->get('name'), 'options' => $fields);
			}

			$label->appendChild(Widget::Select('settings[forum]['.$handle.']', $options));	
			return $label;		
		}

		public function getDiscussionSectionID(){
			return (int)Symphony::Configuration()->get('discussion-section', 'forum');
		}
		
		public function getCommentSectionID(){
			return (int)Symphony::Configuration()->get('comment-section', 'forum');
		}
		
	}

?>