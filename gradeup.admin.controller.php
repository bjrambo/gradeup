<?php

class gradeupAdminController extends gradeup
{

	function init()
	{
	}

	/**
	 * Insert module info.
	 * @return mixed
	 */
	function procGradeupAdminModuleInfo()
	{
		$obj = Context::getRequestVars();

		$obj->module = 'gradeup';

		/** @var moduleController $oModuleController */
		$oModuleController = getController('module');
		if (!$obj->module_srl)
		{
			$output = $oModuleController->insertModule($obj);
			$this->setMessage('success_registed');
		}
		else
		{
			$output = $oModuleController->updateModule($obj);
			$this->setMessage('success_updated');
		}

		if (!$output->toBool())
		{
			return $output;
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispGradeupAdminModuleInfo'));
	}

	/**
	 * Grade up by module config.
	 */
	function procGradeupAdminGradeConfig()
	{
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		$obj = Context::getRequestVars();

		$oModuleController = getController('module');
		if (!$module_config)
		{
			$module_config = new stdClass();
		}
		$module_config->module = 'gradeup';
		$module_config->gradeup_use = $obj->gradeup_use;

		foreach ($obj->gradeup_condition as $key => $val)
		{
			if (!$val['gradeup_priority'])
			{
				$obj->gradeup_condition[$key]['gradeup_priority'] = '1';
			}
			if (!$val['gradeup_remain_date'])
			{
				$obj->gradeup_condition[$key]['gradeup_remain_date'] = '30';
			}
		}

		$module_config->gradeup_condition = $obj->gradeup_condition;
		$module_config->gradeup_msg_use = $obj->gradeup_msg_use;
		$module_config->garadeup_msg_member_srl = $obj->garadeup_msg_member_srl ? $obj->garadeup_msg_member_srl : 4;
		$module_config->garadeup_msg_title = $obj->garadeup_msg_title ? $obj->garadeup_msg_title : '축하합니다.';
		$module_config->gradeup_auto_msg = $obj->gradeup_auto_msg ? $obj->gradeup_auto_msg : '[nick_name]님. [group_name]으로 등업되셨습니다.[enter]앞으로도 많은 활동 부탁드립니다.';
		$module_config->gradeup_confirm_msg = $obj->gradeup_confirm_msg ? $obj->gradeup_confirm_msg : '[nick_name]님. [group_name]으로 등업되셨습니다.[enter]앞으로도 많은 활동 부탁드립니다.';
		$module_config->gradeup_term_msg = $obj->gradeup_term_msg ? $obj->gradeup_term_msg : '[nick_name]님. [remain_date]까지 [group_name]으로 등업되셨습니다.[enter]앞으로도 많은 활동 부탁드립니다.';
		$oModuleController->insertModuleConfig('gradeup', $module_config);

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispGradeupAdminGradeConfig'));
	}

	function procGradeupAdminLogList()
	{
		$oModuleModel = getModel('module');
		$module_config = $oModuleModel->getModuleConfig('gradeup');

		$args = Context::getRequestVars();
		// view_list is array. so if get to view_list is not array, initizlize with array.
		$module_config->view_list = $args->view_list ? $args->view_list : array(
			'member_srl',
			'nick_name',
			'gradeup_type',
			'add_group_srl',
			'old_group_srl',
			'new_group_srl',
			'gradeup_add_type',
			'regdate',
			'remain_date',
			'ipaddress'
		);

		/** @var moduleController $oModuleController */
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('gradeup', $module_config);

		$this->setMessage('success_updated');

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispGradeupAdminGradeLog'));
	}

	function procGradeupAdminTermGroupModify()
	{
		$obj = Context::getRequestVars();

		$args = new stdClass();
		$args->log_srl = $obj->log_srl;
		$args->remain_date = $obj->remain_date . $obj->remain_date_h . $obj->remain_date_i . $obj->remain_date_s;

		$output = executeQuery('gradeup.updateTermGroupLog', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispGradeupAdminTermGroupModify', 'log_srl', $obj->log_srl));
	}

	/**
	 * Add to term groups.
	 * @return object
	 */
	function procGradeupAdminTermGroupAdd()
	{
		$obj = Context::getRequestVars();

		// TODO : It use to return Object?
		if (!$obj->member_srl)
		{
			return;
		}

		$oGradeupModel = getModel('gradeup');

		$args = new stdClass();
		$args->member_srl = $obj->member_srl;
		$args->add_group_srl = $obj->group_srl;
		$args->gradeup_add_type = $obj->gradeup_add_type;
		$args->regdate = $obj->regdate . $obj->regdate_h . $obj->regdate_i . $obj->regdate_s;
		$args->remain_date = $obj->remain_date . $obj->remain_date_h . $obj->remain_date_i . $obj->remain_date_s;
		$args->old_group_srl = $oGradeupModel->getMemberGroupSrl($obj->member_srl);

		$output = executeQuery('gradeup.insertGradeUpTermGroup', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispGradeupAdminTermGroup'));
	}

	/**
	 * Delete to log.
	 * @return object
	 */
	function procGradeupAdminLogDelete()
	{
		$log_srls = Context::get('log_srls');
		if (!$log_srls)
		{
			return new Object(-1, '선택 대상이 없습니다');
		}

		$log_srl_list = explode("@", $log_srls);
		foreach ($log_srl_list as $key => $val)
		{
			$args = new stdClass();
			$args->log_srl = $val;
			$output = $this->DeleteLog($args);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		$this->setMessage('success_deleted');
	}

	/**
	 * delete All data.
	 * @return void
	 */
	function procGradeupAdminLogDeleteAll()
	{
		$this->DeleteLogAll();
		$this->initializeAutoIncrement('gradeup_log');
		$this->setMessage('success_deleted');
	}

	/**
	 * delete to Term log
	 * @return Object
	 */
	function procGradeupAdminTermLogDelete()
	{
		$log_srls = Context::get('log_srls');
		if (!$log_srls)
		{
			return new Object(-1, '선택 대상이 없습니다');
		}

		$log_srl_list = explode("@", $log_srls);
		foreach ($log_srl_list as $key => $val)
		{
			$args = new stdClass();
			$args->log_srl = $val;
			$output = $this->DeleteTermLog($args);
			if ($output->toBool())
			{
				return $output;
			}
		}
		$this->setMessage('success_deleted');
	}

	/**
	 * Delete to All term log
	 */
	function procGradeupAdminTermLogDeleteAll()
	{
		$this->DeleteTermLogAll();
		$this->initializeAutoIncrement('gradeup_term_group');
		$this->setMessage('success_deleted');
	}

	/**
	 * Delete to selected log
	 * @return object
	 */
	function procGradeupAdminConfirmLogDelete()
	{
		$log_srls = Context::get('log_srls');
		if (!$log_srls)
		{
			return new Object(-1, '선택 대상이 없습니다');
		}

		$log_srl_list = explode("@", $log_srls);
		foreach ($log_srl_list as $key => $val)
		{
			$args = new stdClass();
			$args->log_srl = $val;
			$output = $this->DeleteConfirmLog($args);
			if ($output->toBool())
			{
				return $output;
			}
		}
		$this->setMessage('success_deleted');
	}

	/**
	 * Delete to confirm log
	 */
	function procGradeupAdminConfirmLogDeleteAll()
	{
		$this->DeleteConfirmLogAll();
		//auto_increment initialized to number 1
		// TODO(BJRambo): Find function to Auto_increment initialized.

		$this->initializeAutoIncrement('gradeup_confirm_group');
		$this->setMessage('success_deleted');
	}

	/**
	 * Delete grade up log.
	 * @param $args
	 * @return object
	 */
	function DeleteLog($args)
	{
		$output = executeQuery('gradeup.deleteLog', $args);
		return $output;
	}

	/**
	 * Delete All grade up log.
	 * @return object
	 */
	function DeleteLogAll()
	{
		$output = executeQuery('gradeup.deleteLog');
		return $output;
	}

	/**
	 * Delete Term Log.
	 * @param $args
	 * @return object
	 */
	function DeleteTermLog($args)
	{
		$output = executeQuery('gradeup.deleteTermLog', $args);
		return $output;
	}

	/**
	 * Delete All term log.
	 * @return object
	 */
	function DeleteTermLogAll()
	{
		$output = executeQuery('gradeup.deleteTermLog');
		return $output;
	}

	/**
	 * Delete confirm log.
	 * @param $args
	 * @return object
	 */
	function DeleteConfirmLog($args)
	{
		$output = executeQuery('gradeup.deleteConfirmLog', $args);
		return $output;
	}

	/**
	 * Delete All Confirm Log.
	 * @return object
	 */
	function DeleteConfirmLogAll()
	{
		$output = executeQuery('gradeup.deleteConfirmLog');
		return $output;
	}

	/**
	 * initialize Auto increment
	 * @param string $string
	 * @return void
	 */
	function initializeAutoIncrement(string $string)
	{
		$oDB = &DB::getInstance();
		$query = sprintf("alter table %s%s auto_increment=1", $oDB->prefix, $string);
		
		$query = $oDB->_query($query);
		$oDB->_fetch($query);
	}
}
