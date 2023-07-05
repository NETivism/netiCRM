<?php

/**
 * This class contains all the AI function that are called by AJAX
 */
class CRM_AI_Page_AJAX {

  function chat() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if (is_string($jsondata['tone']) && isset($jsondata['tone'])) {
        $tone_style = $jsondata['tone'];
        $data['tone_style'] = $tone_style;
      }
      if (is_string($jsondata['role']) && isset($jsondata['role'])) {
        $ai_role = $jsondata['role'];
        $data['ai_role'] = $ai_role;
      }
      if (is_string($jsondata['content']) && isset($jsondata['content'])) {
        $context = $jsondata['content'];
        $data['context'] = $context;
      }

      if ($tone_style && $ai_role && $context) {
        $system_prompt = ts("You are an %1 in Taiwan who uses Traditional Chinese and is skilled at writing %2 copywriting.",
          array(1 => $ai_role, 2 => $tone_style,)
        );
        $data['prompt'] = array(
          array(
            'role' => 'system',
            'content' => $system_prompt,
          ),
          array(
            'role' => 'user',
            'content' => $context,
          ),
        );
        $token = CRM_AI_BAO_AICompletion::prepareChat($data);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($token);
        CRM_Utils_System::civiExit();
      }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      if (is_string($_GET['token']) && isset($_GET['token']) && is_string($_GET['id']) && isset($_GET['id'])) {
        $token = $_GET['token'];
        $id = $_GET['id'];
        $params = [
          'token' => $token,
          'id' => $id,
          'stream' => TRUE,
        ];
        $result = CRM_AI_BAO_AICompletion::Chat($params);
        header('Content-Type: text/event-stream; charset=utf-8');
        echo json_encode($result);
        CRM_Utils_System::civiExit();
      }
    }
  }

  function getTemplateList() {
  }

  function getTemplate() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if (is_string($jsondata['id']) && isset($jsondata['id'])) {
        $acId = $jsondata['id'];
      }
      if ($acId) {
        $getTemplateResult = CRM_AI_BAO_AICompletion::getTemplate($acId);
        if (is_array($getTemplateResult) && !empty($getTemplateResult)) {
          $result = [
            'status' => "success",
            'message' => "Template retrieved successfully",
            'data' => $getTemplateResult,
          ];
        }
        elseif ($getTemplateResult == FALSE) {
          $result = [
            'status' => "Failed",
            'message' => "Failed to retrieve template",
          ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        CRM_Utils_System::civiExit();
      }
    }
  }

  function setTemplate() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] == 'application/json') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if (is_string($jsondata['id']) && isset($jsondata['id'])) {
        $acId = $jsondata['id'];
        $data['id'] = $acId;
      }
      if (is_string($jsondata['is_template']) && isset($jsondata['is_template'])) {
        $acIsTemplate = $jsondata['is_template'];
        $data['is_template'] = $acIsTemplate;
      }
      if (is_string($jsondata['template_title']) && isset($jsondata['template_title'])) {
        $acTemplateTitle = $jsondata['template_title'];
        $data['template_title'] = $acTemplateTitle;
      }
      if ($acId && $acIsTemplate && $acTemplateTitle) {
        $setTemplateResult = CRM_AI_BAO_AICompletion::setTemplate($data);
        $result = array();
        if ($setTemplateResult == TRUE) {
          //set template successful return true
          $result = [
            'status' => "success",
            'message' => "AI completion is set as template successfully",
            'data' => [
              'id' => $acId,
              'is_template' => $acTemplateTitle,
            ],
          ];
        }
        elseif ($setTemplateResult == FALSE) {
          //Originally 1 returns False
          $result = [
            'status' => "success",
            'message' => "AI completion has already been set as a template",
            'data' => [
              'id' => $acId,
              'is_template' => $acTemplateTitle,
            ],
          ];
        }
        else {
          //If it cannot be set to 1 throw Error
          //TODO: return error
          $result = [
            'status' => "Failed",
            'message' => "Failed to set AI completion as template",
            'data' => [
              'id' => $acId,
              'is_template' => $acTemplateTitle,
            ],
          ];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result);
        CRM_Utils_System::civiExit();
      }
    }
  }

  function setShare() {
  }
}