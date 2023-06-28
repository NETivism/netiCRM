<?php

/**
 * This class contains all the AI function that are called by AJAX
 */
class CRM_AI_Page_AJAX {

  function chat() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $jsonString = file_get_contents('php://input');
      $jsondata = json_decode($jsonString, true);
      if (is_string($jsondata['tone']) && isset(($jsondata['tone']))) {
        $tone_style = $jsondata['tone'];
      }
      if (is_string($jsondata['role']) && isset(($jsondata['role']))) {
        $ai_role = $jsondata['role'];
      }
      if (is_string($jsondata['content']) && isset(($jsondata['content']))) {
        $context = $jsondata['content'];
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
        return $token;
      }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      //TODO : GET Token and send to CRM_AI_BAO_AICompletion::Chat
      //CRM_AI_BAO_AICompletion::Chat($token);
    }
  }

  function getTemplateList() {
  }

  function getTemplate() {
  }

  function setTemplate() {
  }

  function setShare() {
  }
}