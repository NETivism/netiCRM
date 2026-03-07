{*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if $suppress}
<div class="messages">
  {$suppress}
</div>
{else}
<style>
{literal}

/* --- speech bubble (tail points LEFT toward robot head) --- */
.crm-fatal-bubble {
  position: relative;
  flex: 1;
  min-width: 0;
  background: #f3f5ff;
  border: 2.5px solid #667eea;
  border-radius: 18px;
  padding: 14px 18px;
  font-size: 14px;
  line-height: 1.7;
  color: #444;
  text-align: left;
  width: 60%;
  min-width: 380px;
}

/* outer triangle (border colour) */
.crm-fatal-bubble::before {
  content: '';
  position: absolute;
  left: 160px;
  top: 100%;
  border: 16px solid transparent;
  border-top: 16px solid #667eea;
  border-bottom: 0;
}

/* inner triangle (fill colour) */
.crm-fatal-bubble::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 160px;
  top: calc(100% - 3px);
  border: 16px solid transparent;
  border-top: 16px solid #f3f5ff;
  border-bottom: 0;
}

.crm-fatal-back {
  display: inline-block;
  font-size: 14px;
  color: #667eea;
  text-decoration: underline;
  cursor: pointer;
  margin-top: 4px;
}
.crm-fatal-back:hover {
  color: #4a5cc7;
}

/* SVG animation definitions */

/* 1. Arm twitch animation — rotates from the shoulder end (left edge of arm group) */
@keyframes crm-fatal-twitch {
  0%, 80% { transform: rotate(0deg); }
  82% { transform: rotate(-15deg); }
  84% { transform: rotate(10deg); }
  86% { transform: rotate(-5deg); }
  88% { transform: rotate(5deg); }
  90%, 100% { transform: rotate(0deg); }
}
.crm-fatal-twitch {
  transform-box: fill-box;
  transform-origin: 0 50%;
  animation: crm-fatal-twitch 4s infinite cubic-bezier(0.36, 0.07, 0.19, 0.97);
}

/* 2. Smoke float animation */
@keyframes crm-fatal-float-smoke {
  0% { transform: translateY(0) scale(0.8); opacity: 0; }
  20% { opacity: 0.6; }
  100% { transform: translateY(-150px) scale(2.5); opacity: 0; }
}
.crm-fatal-smoke {
  opacity: 0;
  animation: crm-fatal-float-smoke 3s infinite linear;
  transform-origin: center;
}
.crm-fatal-smoke-1 { animation-delay: 0s; }
.crm-fatal-smoke-2 { animation-delay: 0.8s; left: 10px; }
.crm-fatal-smoke-3 { animation-delay: 1.6s; }
.crm-fatal-smoke-4 { animation-delay: 2.4s; }

/* 3. Spark shoot animation */
@keyframes crm-fatal-spark-shoot {
  0% { transform: translate(0, 0) scale(1); opacity: 1; }
  50% { opacity: 1; }
  100% { transform: translate(var(--tx), var(--ty)) scale(0); opacity: 0; }
}
.crm-fatal-spark {
  opacity: 0;
  animation: crm-fatal-spark-shoot 1.5s infinite ease-out;
}
.crm-fatal-spark-1 { --tx: -40px; --ty: -50px; animation-delay: 0.2s; animation-duration: 0.8s; }
.crm-fatal-spark-2 { --tx: 30px; --ty: -60px; animation-delay: 0.7s; animation-duration: 1.1s; }
.crm-fatal-spark-3 { --tx: -20px; --ty: -80px; animation-delay: 1.2s; animation-duration: 0.9s; }
.crm-fatal-spark-4 { --tx: 50px; --ty: -40px; animation-delay: 0.5s; animation-duration: 1.3s; }

/* 4. Glitch flicker animation */
@keyframes crm-fatal-flicker {
  0%, 100% { opacity: 1; }
  33% { opacity: 0.4; }
  36% { opacity: 1; }
  39% { opacity: 0.2; }
  42% { opacity: 1; }
  70% { opacity: 1; }
  72% { opacity: 0.5; }
  74% { opacity: 1; }
}
.crm-fatal-flicker {
  animation: crm-fatal-flicker 3s infinite;
}

/* 5. Alert light rapid blink (pink) */
@keyframes crm-fatal-alert-blink {
  0%, 49% { fill: #fb7185; filter: drop-shadow(0 0 8px #fb7185); }
  50%, 100% { fill: #881337; filter: none; }
}
.crm-fatal-alert {
  animation: crm-fatal-alert-blink 1s infinite;
}

/* 6. Wheel rolling animation */
@keyframes crm-fatal-wheel-spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
.crm-fatal-wheel-spin {
  transform-box: fill-box;
  transform-origin: center;
  animation: crm-fatal-wheel-spin 6s infinite ease-in-out;
}

/* Text styles */
.crm-fatal-title {
  text-align: center;
  margin-top: -40px;
  z-index: 10;
  position: relative;
}
.crm-fatal-title h1 {
  font-size: 3rem;
  margin: 0 0 10px 0;
  color: #1e293b;
  letter-spacing: 2px;
}
.crm-fatal-title p {
  font-size: 1.2rem;
  color: #64748b;
  margin: 0;
  max-width: 600px;
  line-height: 1.5;
}

.crm-fatal-action-btn {
  display: inline-block;
  margin-top: 25px;
  padding: 12px 28px;
  background-color: #8da5e3; /* matches robot's pastel blue-purple */
  color: white;
  text-decoration: none;
  border-radius: 30px;
  font-weight: bold;
  transition: all 0.3s ease;
  border: 2px solid #8da5e3;
}
.crm-fatal-action-btn:hover {
  background-color: #6a88d4;
  border-color: #6a88d4;
  box-shadow: 0 0 15px rgba(106, 136, 212, 0.4);
}

@media (max-width: 768px) {
  .crm-fatal-title h1 { font-size: 2rem; }
  .crm-fatal-title p { font-size: 1rem; padding: 0 20px; }
}
{/literal}
</style>

<div class="crm-fatal-wrap">

  <div class="crm-fatal-scene">

    <!-- comic speech bubble -->
    <div class="crm-fatal-bubble">
      {if $message}
        {$message}
      {else}
        {ts}We are very sorry that there an error occurred. Please contact system administrator for further support. Thanks for your help in improving this open source project.{/ts}
      {/if}
      <a class="crm-fatal-back" href="javascript:history.back()">{ts}Go Back{/ts}</a>
    </div>

    <!-- robot lying on its side, head on the left, feet up on the right -->
    <div class="crm-fatal-robot-wrap">
      <!--
        viewBox crops from y=240 so the robot fills the canvas with minimal top whitespace.
        The arm tip reaches approximately SVG y=265, giving ~25 units top margin.
      -->
      <svg viewBox="100 240 800 300" width="800" height="400" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <!-- Gradient definitions -->
          <!-- Robot body gradient (desaturated blue-purple #002893 -> pastel) -->
          <linearGradient id="crm-fatal-body-grad" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#e0e7fa" />
            <stop offset="100%" stop-color="#a3b5e6" />
          </linearGradient>
          <!-- Soft metal gradient (joints / treads) -->
          <linearGradient id="crm-fatal-dark-metal" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#94a3b8" />
            <stop offset="100%" stop-color="#64748b" />
          </linearGradient>
          <!-- Screen highlight gradient (blue-grey tones) -->
          <linearGradient id="crm-fatal-screen-grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#475569" />
            <stop offset="50%" stop-color="#334155" />
            <stop offset="100%" stop-color="#1e293b" />
          </linearGradient>
          <!-- Caution stripe pattern (soft version) -->
          <pattern id="crm-fatal-caution-stripe" width="20" height="20" patternUnits="userSpaceOnUse" patternTransform="rotate(45)">
            <rect width="10" height="20" fill="#fde047" />
            <rect x="10" width="10" height="20" fill="#94a3b8" />
          </pattern>

          <!-- Filter definitions -->
          <!-- Glow filter -->
          <filter id="crm-fatal-glow" x="-20%" y="-20%" width="140%" height="140%">
            <feGaussianBlur stdDeviation="4" result="blur" />
            <feComposite in="SourceGraphic" in2="blur" operator="over" />
          </filter>
          <!-- Smoke blur filter -->
          <filter id="crm-fatal-smoke-blur">
            <feGaussianBlur stdDeviation="8" />
          </filter>
          <!-- Terminal text glow: soft green bloom layered under sharp text -->
          <filter id="crm-fatal-terminal-glow" x="-60%" y="-60%" width="220%" height="220%">
            <feGaussianBlur in="SourceGraphic" stdDeviation="2.5" result="blur1" />
            <feGaussianBlur in="SourceGraphic" stdDeviation="1" result="blur2" />
            <feMerge>
              <feMergeNode in="blur1" />
              <feMergeNode in="blur2" />
              <feMergeNode in="SourceGraphic" />
            </feMerge>
          </filter>
          <!-- Clip mask matching the torso chest panel (80x80 centered at 0,0) -->
          <clipPath id="crm-fatal-panel-clip">
            <rect x="-38" y="-38" width="76" height="76" rx="8" />
          </clipPath>
        </defs>

        <!-- Ground shadow (light grey to match white background) -->
        <ellipse cx="500" cy="520" rx="350" ry="25" fill="#f1f5f9" />
        <ellipse cx="450" cy="525" rx="150" ry="15" fill="#e2e8f0" />

        <!-- Scattered parts -->
        <g id="crm-fatal-debris" opacity="0.9">
          <!-- Fallen gear: proper 8-tooth gear -->
          <g transform="translate(500, 500) rotate(15) scale(0.5)">
            <!-- Gear body (drawn first so teeth overlap the rim edge) -->
            <circle cx="0" cy="0" r="28" fill="url(#crm-fatal-dark-metal)" />
            <!-- 8 teeth as rounded rects at 45° intervals -->
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(45)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(90)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(135)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(180)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(225)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(270)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(315)" />
            <!-- Inner face -->
            <circle cx="0" cy="0" r="22" fill="#cbd5e1" />
            <!-- Hub and bolt holes -->
            <circle cx="0" cy="0" r="8" fill="#475569" />
            <circle cx="0" cy="-14" r="3" fill="#94a3b8" />
            <circle cx="12" cy="7" r="3" fill="#94a3b8" />
            <circle cx="-12" cy="7" r="3" fill="#94a3b8" />
          </g>
          <g transform="translate(520, 480) rotate(18) scale(0.4)">
            <!-- Gear body (drawn first so teeth overlap the rim edge) -->
            <circle cx="0" cy="0" r="28" fill="url(#crm-fatal-dark-metal)" />
            <!-- 8 teeth as rounded rects at 45° intervals -->
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(45)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(90)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(135)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(180)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(225)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(270)" />
            <rect x="-5" y="-41" width="10" height="14" rx="2" fill="url(#crm-fatal-dark-metal)" transform="rotate(315)" />
            <!-- Inner face -->
            <circle cx="0" cy="0" r="22" fill="#cbd5e1" />
            <!-- Hub and bolt holes -->
            <circle cx="0" cy="0" r="8" fill="#475569" />
            <circle cx="0" cy="-14" r="3" fill="#94a3b8" />
            <circle cx="12" cy="7" r="3" fill="#94a3b8" />
            <circle cx="-12" cy="7" r="3" fill="#94a3b8" />
          </g>

          <!-- Scattered screws -->

          <rect x="680" y="525" width="15" height="8" rx="2" fill="#cbd5e1" transform="rotate(-45 680 525)" />
          <line x1="682" y1="529" x2="693" y2="529" stroke="#64748b" stroke-width="2" transform="rotate(-45 680 525)" />

          <!-- Broken wire segment -->
          <path d="M 600 515 Q 620 490 640 520" fill="none" stroke="#fca5a5" stroke-width="4" stroke-linecap="round" />
          <path d="M 605 518 Q 625 500 635 525" fill="none" stroke="#93c5fd" stroke-width="3" stroke-linecap="round" />
        </g>

        <!-- Robot body group (fallen over) -->
        <g id="crm-fatal-robot" transform="translate(0, 30)">

          <!-- Base: circular wheel with rolling animation -->
          <g id="crm-fatal-base" transform="translate(575, 445)">
            <!-- Outer tire ring -->
            <circle cx="0" cy="0" r="52" fill="url(#crm-fatal-dark-metal)" />
            <circle cx="0" cy="0" r="44" fill="#cbd5e1" />
            <!-- Spinning spokes and hub -->
            <g class="crm-fatal-wheel-spin">
              <line x1="0" y1="-40" x2="0" y2="-14" stroke="#94a3b8" stroke-width="5" stroke-linecap="round" />
              <line x1="28" y1="-28" x2="10" y2="-10" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" />
              <line x1="40" y1="0" x2="14" y2="0" stroke="#94a3b8" stroke-width="5" stroke-linecap="round" />
              <line x1="28" y1="28" x2="10" y2="10" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" />
              <line x1="0" y1="40" x2="0" y2="14" stroke="#94a3b8" stroke-width="5" stroke-linecap="round" />
              <line x1="-28" y1="28" x2="-10" y2="10" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" />
              <line x1="-40" y1="0" x2="-14" y2="0" stroke="#94a3b8" stroke-width="5" stroke-linecap="round" />
              <line x1="-28" y1="-28" x2="-10" y2="-10" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" />
              <!-- Hub -->
              <circle cx="0" cy="0" r="14" fill="url(#crm-fatal-dark-metal)" />
              <circle cx="0" cy="0" r="8" fill="#94a3b8" />
            </g>
          </g>

          <!-- Torso (lying sideways) -->
          <g id="crm-fatal-torso" transform="translate(420, 390) rotate(80)">
            <!-- Main shell -->
            <rect x="-70" y="-90" width="140" height="180" rx="20" fill="url(#crm-fatal-body-grad)" />
            <rect x="-60" y="-80" width="120" height="160" rx="15" fill="#f8fafc" />

            <!-- Caution stripe border -->
            <rect x="-50" y="-70" width="100" height="15" rx="5" fill="url(#crm-fatal-caution-stripe)" />
            <rect x="-50" y="55" width="100" height="15" rx="5" fill="url(#crm-fatal-caution-stripe)" />

            <!-- Chest panel (blown-open interior hole) -->
            <rect x="-40" y="-40" width="80" height="80" rx="10" fill="#475569" />
            <!-- Burnt circuit interior -->
            <path d="M-30,-30 L30,30 M-30,30 L30,-30" stroke="#334155" stroke-width="2" />
            <!--
              Terminal display: error text 10px, terminal green (#22c55e),
              with soft bloom glow. Clipped to the panel bounds.
            -->
            <g clip-path="url(#crm-fatal-panel-clip)" filter="url(#crm-fatal-terminal-glow)">
              <g transform="rotate(-160)" font-size="10" font-family="monospace" fill="#22c55e">
                <text x="-28" y="-22">&gt; {if $type eq "data-error"}{ts}Data Error{/ts}{else}{ts}Internal error{/ts}{/if}</text>
                <text x="-34" y="-8">&gt; {if $type eq "data-error"}Search Err{else}FATAL{/if}</text>
                <text x="-28" y="6">&gt; {$smarty.now|date_format:'%Y-%m-%d'}</text>
                <text x="-26" y="20">&gt; {$smarty.now|date_format:'%H:%M:%S'}</text>
              </g>
            </g>
            <!-- Exploded pastel wires -->
            <path d="M-20,-20 Q-50,-60 -80,-10" fill="none" stroke="#fca5a5" stroke-width="4" stroke-linecap="round" />
            <path d="M-30,30 Q-70,50 -60,20" fill="none" stroke="#fde047" stroke-width="4" stroke-linecap="round" />
            <path d="M20,-10 Q60,-40 80,10" fill="none" stroke="#93c5fd" stroke-width="4" stroke-linecap="round" />

            <!-- Chest error indicator lights -->
            <circle cx="30" cy="-62" r="6" class="crm-fatal-alert" />
          </g>

          <!-- Smoke rising from chest (rendered above body) -->
          <g id="crm-fatal-smoke-group" filter="url(#crm-fatal-smoke-blur)" transform="translate(430, 420)">
            <circle cx="0" cy="0" r="20" fill="#cbd5e1" class="crm-fatal-smoke crm-fatal-smoke-1" />
            <circle cx="-15" cy="-10" r="25" fill="#e2e8f0" class="crm-fatal-smoke crm-fatal-smoke-2" />
            <circle cx="10" cy="-20" r="18" fill="#94a3b8" class="crm-fatal-smoke crm-fatal-smoke-3" />
            <circle cx="-5" cy="-5" r="22" fill="#cbd5e1" class="crm-fatal-smoke crm-fatal-smoke-4" />
          </g>

          <!--
            Right arm: extends from shoulder joint upward-right at -65°.
            Structure mirrors crm-fatal-left-arm (dark-metal segment → joint circle → body-grad segment → claw).
            Upper arm removed; entire arm twitches from the shoulder pivot.
          -->
          <g id="crm-fatal-right-arm-group">
            <!-- Shoulder joint -->
            <circle cx="330" cy="340" r="15" fill="url(#crm-fatal-dark-metal)" />
            <circle cx="330" cy="340" r="10" fill="#cbd5e1" />

            <!-- Arm positioned at shoulder, pointing upper-right at 65° above horizontal -->
            <g transform="translate(333, 348) rotate(-110)">
              <!-- Twitch animation: rotates from local origin (0,0) = shoulder point -->
              <g class="crm-fatal-twitch">
                <rect x="0" y="-10" width="80" height="20" rx="10" fill="url(#crm-fatal-dark-metal)" />
                <circle cx="80" cy="0" r="15" fill="#a3b5e6" />
                <rect x="80" y="-8" width="70" height="16" rx="8" fill="url(#crm-fatal-body-grad)" />
                <!-- Rounded claw -->
                <path d="M 140 -8 C 160 -8 160 8 140 8" fill="none" stroke="url(#crm-fatal-dark-metal)" stroke-width="8" stroke-linecap="round" />
                <!-- Palm indicator light -->
                <circle cx="150" cy="0" r="6" fill="#fb7185" class="crm-fatal-flicker" filter="url(#crm-fatal-glow)" />
              </g>
            </g>
          </g>
          <g id="crm-fatal-left-arm-group">
            <!-- Shoulder joint -->
            <circle cx="350" cy="480" r="15" fill="url(#crm-fatal-dark-metal)" />
            <circle cx="350" cy="480" r="10" fill="#cbd5e1" />
            <!-- Left arm pinned underneath -->
            <g id="crm-fatal-left-arm" transform="translate(358, 478) rotate(175)">
              <rect x="0" y="-10" width="80" height="20" rx="10" fill="url(#crm-fatal-dark-metal)" />
              <circle cx="80" cy="0" r="15" fill="#a3b5e6" />
              <rect x="80" y="-8" width="70" height="16" rx="8" fill="url(#crm-fatal-body-grad)" />
              <!-- Rounded claw -->
              <path d="M 140 -8 C 160 -8 160 8 140 8" fill="none" stroke="url(#crm-fatal-dark-metal)" stroke-width="8" stroke-linecap="round" />
            </g>
          </g>

          <!-- Sparks at broken body joint -->
          <g transform="translate(420, 400)">
            <!-- Spark particles -->
            <line x1="0" y1="0" x2="-10" y2="-15" stroke="#fca5a5" stroke-width="4" stroke-linecap="round" class="crm-fatal-spark crm-fatal-spark-1" filter="url(#crm-fatal-glow)" />
            <line x1="0" y1="0" x2="15" y2="-20" stroke="#fde047" stroke-width="3" stroke-linecap="round" class="crm-fatal-spark crm-fatal-spark-2" filter="url(#crm-fatal-glow)" />
            <line x1="0" y1="0" x2="-5" y2="-25" stroke="#fb7185" stroke-width="3" stroke-linecap="round" class="crm-fatal-spark crm-fatal-spark-3" filter="url(#crm-fatal-glow)" />
            <line x1="0" y1="0" x2="20" y2="-10" stroke="#fef08a" stroke-width="4" stroke-linecap="round" class="crm-fatal-spark crm-fatal-spark-4" filter="url(#crm-fatal-glow)" />
          </g>

          <!-- Head (detached; antenna pointing upward, slight backward tilt) -->
          <g id="crm-fatal-head" transform="translate(260, 390) rotate(-50)">
            <!-- Broken antenna with small ball on top -->
            <path d="M 0 41 L 10 70 L 20 70" fill="none" stroke="url(#crm-fatal-dark-metal)" stroke-width="8" stroke-linecap="round" />
            <circle cx="25" cy="68" r="7" fill="#fb7185" class="crm-fatal-flicker" filter="url(#crm-fatal-glow)" />
            <!-- Small sparks at break point -->
            <circle cx="0" cy="40" r="5" fill="#fde047" class="crm-fatal-flicker" filter="url(#crm-fatal-glow)" />

            <!-- Head shell -->
            <rect x="-55" y="-50" width="110" height="90" rx="25" fill="url(#crm-fatal-body-grad)" />

            <!-- Screen visor -->
            <rect x="-45" y="-35" width="90" height="60" rx="15" fill="url(#crm-fatal-screen-grad)" />
            <rect x="-40" y="-30" width="80" height="50" rx="10" fill="#1e293b" />

            <!-- Face expression (cute dizzy face) -->
            <g class="crm-fatal-face">
              <!-- Left eye: > -->
              <g transform="translate(-18, -8)">
                <path d="M-6,-6 L3,0 L-6,6" fill="none" stroke="#fecaca" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" filter="url(#crm-fatal-glow)" />
              </g>

              <!-- Right eye: < (with flicker) -->
              <g transform="translate(18, -8)" class="crm-fatal-flicker">
                <path d="M6,-6 L-3,0 L6,6" fill="none" stroke="#fecaca" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" filter="url(#crm-fatal-glow)" />
              </g>

              <!-- Small sad mouth -->
              <path d="M-5,10 Q0,5 5,10" fill="none" stroke="#fecaca" stroke-width="3" stroke-linecap="round" />

              <!-- Blush marks -->
              <ellipse cx="-24" cy="10" rx="6" ry="3" fill="#fda4af" opacity="0.8" />
              <ellipse cx="24" cy="10" rx="6" ry="3" fill="#fda4af" opacity="0.8" />
            </g>

            <!-- Chin vent slots -->
            <line x1="-15" y1="30" x2="15" y2="30" stroke="#94a3b8" stroke-width="4" stroke-linecap="round" />
          </g>
        </g>
      </svg>
    </div>

  </div><!-- /.crm-fatal-scene -->

</div>
{/if}
