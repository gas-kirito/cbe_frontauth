<?php

// This is a PLUGIN TEMPLATE for Textpattern CMS.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'cbe_members';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = 'a.1.2';
$plugin['author'] = 'Claire Brione';
$plugin['author_uri'] = 'http://www.clairebrione.com/';
$plugin['description'] = 'cbe_frontauth companion: users accounts management (change and reset passwords)';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public              : only on the public side of the website (default)
// 1 = public+admin        : on both the public and admin side
// 2 = library             : only when include_plugin() or require_plugin() is called
// 3 = admin               : only on the admin side (no AJAX)
// 4 = admin+ajax          : only on the admin side (AJAX supported)
// 5 = public+admin+ajax   : on both the public and admin side (AJAX supported)
$plugin['type'] = '0';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '0';

// Plugin 'textpack' is optional. It provides i18n strings to be used in conjunction with gTxt().
// Syntax:
// ## arbitrary comment
// #@event
// #@language ISO-LANGUAGE-CODE
// abc_string_name => Localized String

/** Uncomment me, if you need a textpack
$plugin['textpack'] = <<< EOT
#@admin
#@language en-gb
abc_sample_string => Sample String
abc_one_more => One more
#@language de-de
abc_sample_string => Beispieltext
abc_one_more => Noch einer
EOT;
**/
// End of textpack

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
/**************************************************
 **
 ** Local lang values, possible customisation here
 **
 **************************************************/

function _cbe_mb_lang()
{
    return( array( 'all_fields_required'    => "Merci de remplir tous les champs"
                 , 'new_password_error'     => "Les valeurs du nouveau mot de passe ne correspondent pas"
                 , 'old_password_error'     => "Ancien mot de passe invalide"
                 , 'passwords_must_be_diff' => "Ancien et nouveau mots de passe doivent être différents"
                 , 'password_too_short'     => "Nouveau mot de passe trop court (6 caractères minimum)"
                 , 'register'               => "Envoyer"
                 )
          ) ;
}

/* =========================== Stop editing =========================== */

/**************************************************
 **
 ** Available tags
 **
 **************************************************/

// -- Self registration
// -------------------------------------------------------------------
function cbe_members( $atts, $thing = '' )
{
    /* ... (to be continued) ... */
}

// -------------------------------------------------------------------
// -- Member management utilities
// -------------------------------------------------------------------

// -- Generates fields for register form
// -------------------------------------------------------------------
function _cbe_mb_fInput( $field, $type, $data = null )
{
    $out = array() ;
    if( $data !== null )
        extract( $data ) ;

    if( $type !== 'submit' )
        $out[] = '<label for="'. $field .'">'. _cbe_mb_gTxt( $field ) .'</label>' ;
    $out[] = fInput( $type
                   , $field
                   , isset( $$field ) ? $$field : ''
                   , '', '', '', '', '', $field ) ;
    return( join( n, $out ) ) ;
}

/**************************************************
 **
 ** Functions to be plugged into cbe_frontauth
 **
 **************************************************/

register_callback( 'cbe_members_reset_password' , 'cbefrontauth.reset_password'  ) ;
register_callback( 'cbe_members_change_password', 'cbefrontauth.change_password' ) ;

// -- Reset password
// -- Available steps :
// -- cbe_fa_before_login
// -- cbe_fa_after_login
// -------------------------------------------------------------------
function cbe_members_reset_password( $event, $step, $data=null )
{
    global $prefs ;

    if( $step == 'cbe_fa_before_login' )
    {
        if( $data === null )
        {
            return( _cbe_mb_passwdcr_form( 'reset', $step ) ) ;
        }
        else
        {
            extract( $data ) ;
            //        ^ contains $p_userid, $login_with, $tag_error, $class_error

            $return_url = PROTOCOL.$prefs['siteurl']
                        . preg_replace( "/[?&]reset=1/", "", serverSet('REQUEST_URI') ) ;
            $out = array() ;
            extract( _cbe_mb_get_author( $login_with, $p_userid ) ) ;
            //        ^contains $newpass, $name, $email

            $out[] = $newpass ? _cbe_mb_set_author_pass( $newpass, $name, $email, $return_url, $p_userid )
                              : doTag( _cbe_mb_gTxt( 'unknown_author', array( '{name}' => $p_userid ) )
                                     , $tag_error, $class_error ) ;
            $out[] = cbe_frontauth_link( array( 'label' => _cbe_mb_gTxt( 'home' )
                                              , 'link'  => $return_url
                                              , 'target' => '_self', 'wraptag' => 'p' ) ) ;
            return( join( n, $out ) ) ;
        }
    }
    elseif( $step == 'cbe_fa_after_login' )
    {
        return( _cbe_mb_passwdcr_link( 'reset', array( 'label' => _cbe_mb_gTxt( 'password_forgotten' ) ) ) ) ;
    }
}

// -- Change password
// -- Available steps :
// -- cbe_fa_before_logout
// -- cbe_fa_after_logout
// -------------------------------------------------------------------
function cbe_members_change_password( $event, $step, $data=null )
{
    global $prefs ;

    if( $step == 'cbe_fa_before_logout' )
    {
        if( $data === null )
        {
            return( _cbe_mb_passwdcr_form( 'change', $step ) ) ;
        }
        else
        {
            extract( $data ) ;
            //       ^ contains $p_userid, $p_password, $p_password_1, $p_password_2, $tag_error, $class_error

            $controls = array( 'all_fields_required'
                                => "empty( \$p_password ) || empty( \$p_password_1 ) || empty( \$p_password_2 )"
                             , 'old_password_error'
                                => "txp_validate( \$p_userid, \$p_password, false ) === false"
                             , 'new_password_error'
                                => "\$p_password_1 !== \$p_password_2"
                             , 'passwords_must_be_diff'
                                => "\$p_password_1 === \$p_password"
                             , 'password_too_short'
                                => "strlen( \$p_password_1 ) < 6"
                             ) ;

            $iserror = false ;
            $out = array() ;

            foreach( $controls as $err => $cond )
            {
                if( eval( "return( $cond ) ;" ) )
                {
                    $iserror = true ;
                    $out[] = _cbe_mb_message( $err, $tag_error, $class_error ) ;
                    break ;
                }
            }

            if( ! $iserror )
            {
                $return_url = PROTOCOL.$prefs['siteurl']
                            . preg_replace( "/[?&]change=1/", "", serverSet('REQUEST_URI') ) ;

                $out[] = _cbe_mb_set_author_pass( $p_password_1, $p_userid
                                                , fetch( 'email', 'txp_users', 'name', $p_userid )
                                                , $return_url ) ;
                $out[] = cbe_frontauth_link( array( 'label' => _cbe_mb_gTxt( 'revert' )
                                                  , 'link'  => $return_url
                                                  , 'target' => '_self', 'wraptag' => 'p' ) ) ;
            }

            if( $iserror )
                $out[] =  _cbe_mb_passwdcr_form( 'change', $step, compact( array_keys( $data ) ) ) ;

            return( join( n, $out ) ) ;
        }
    }
    elseif( $step == 'cbe_fa_after_logout' )
    {
        return( _cbe_mb_passwdcr_link( 'change', array( 'label' => _cbe_mb_gTxt( 'change_password' ) ) ) ) ;
    }
}

// -------------------------------------------------------------------
// -- Callbacks and general utilities
// -------------------------------------------------------------------

// -- Gets and returns admin lang strings
// -------------------------------------------------------------------
function _cbe_mb_gTxt( $text, $atts = array() )
{
    static $aTexts = array() ;
    if( ! $aTexts )
        $aTexts = load_lang_event( 'admin' ) + _cbe_mb_lang() ;

    if( ($sText = gTxt( $text )) === $text && isset( $aTexts[ $text ] ) )
    {
        $sText = strtr( $aTexts[ $text ], $atts ) ;
    }

    return( $sText ) ;
}

// -- Returns taggified message
// -------------------------------------------------------------------
function _cbe_mb_message( $text, $tag, $class )
{
    return( doTag( _cbe_mb_gTxt( $text ), $tag, $class ) ) ;
}

// -- Generates link for resetting or changing password
// -------------------------------------------------------------------
function _cbe_mb_passwdcr_link( $type, $atts )
{
    extract( lAtts( array ( 'label' => '' )
                    + _cbe_fa_format()
                  , $atts
                  )
           ) ;

    return( cbe_frontauth_link( array( 'label' => _cbe_mb_gTxt( $type=='reset' ? 'password_forgotten'
                                                                               : 'change_password' )
                                     , 'link' => $type.'=1', 'target' => '_get'
                                     , 'wraptag' => 'p', 'class' => $class ? $class : '' ) ) ) ;
}

// -- Wrap form fields in form
// -------------------------------------------------------------------
function _cbe_mb_form( $contents, $action, $step = null, $method = null )
{
    return( '<form action="'. $action .'" method="'. ($method !== null ? $method : 'post') .'">'
            .n. $contents
            . ($step !== null ? n . sInput( $step ) : '')
            .n. '</form>' ) ;
}

// -- Generates form for resetting or changing password
// -------------------------------------------------------------------
function _cbe_mb_passwdcr_form( $type, $step, $data=null )
{
    $out = array() ;
    if( $data !== null )
        extract( $data ) ;

    if( $type == 'reset' )
        $out[] = cbe_frontauth_logname( array( 'class' => 'edit', 'wraptag' => 'p', 'break' => 'br' ) ) ;
    else
    { // old pass, new pass, new pass
        $label_new = _cbe_mb_gTxt( 'new_password' ) ;
        $out[] = cbe_frontauth_password( array( 'class' => 'edit')
                                       , isset( $p_password ) ? $p_password : null ) ;
        $out[] = _cbe_fa_identity( 'new_password', array( 'label' => $label_new, 'label_sfx' => '_1' )
                                 , isset( $p_password_1 ) ? $p_password_1 : null ) ;
        $out[] = _cbe_fa_identity( 'new_password', array( 'label' => $label_new, 'label_sfx' => '_2' )
                                 , isset( $p_password_2 ) ? $p_password_2 : null ) ;
    }

    $out[] = cbe_frontauth_submit( array( 'label' => _cbe_mb_gTxt( $type=='reset' ? 'password_reset'
                                                                                  : 'change_password' )
                                        , 'class' => 'publish', 'wraptag' => 'p' ) ) ;

    return( _cbe_mb_form( join( n, $out ), page_url( array() ), $step ) ) ;
}

// -- Mails new password (changed or reset)
// -------------------------------------------------------------------
function _cbe_mb_get_author( $login_with, $name )
{
    include_once( txpath.'/lib/txplib_admin.php' ) ;

    $usermail = safe_rows( 'name, email', 'txp_users'
                         , ($login_with != 'email' ? "name" : "email") ."='$name'" ) ;

    if( count( $usermail ) != 1 && $login_with == 'auto' )
        $usermail = safe_rows( 'name, email', 'txp_users', "email='$name'" ) ;

    if( count( $usermail ) == 1 )
        return( array( 'name'    => $usermail[0][ 'name'  ]
                     , 'email'   => $usermail[0][ 'email' ]
                     , 'newpass' => generate_password( 12 )
                     ) ) ;

    return( array( 'name' => '', 'email' => '', 'newpass' => '' ) ) ;
}

// -- Mails new password (changed or reset)
// -------------------------------------------------------------------
function _cbe_mb_send_new_password( $pass, $email, $name, $return_url, $givenid )
{
    global $prefs, $txp_user, $sitename ;
    $curr_user = $txp_user ;

/*    $message = _cbe_mb_gTxt('greeting').' '.$givenid.','.
*        n.n._cbe_mb_gTxt('your_password_is').': '.$pass.
*        n.n._cbe_mb_gTxt('log_in_at').':'.n.hu;
*/
    $message = _cbe_mb_gTxt('password_changed').n.n.
        _cbe_mb_gTxt('username').': '.$givenid.n.
        _cbe_mb_gTxt('your_password_is').': '.$pass.n.n.
        _cbe_mb_gTxt('log_in_at').':'.n.hu;
    $txp_user = $name ; // txpMail() recherche RealName à partir de name
    $out = txpMail($email, "[$sitename] "._cbe_mb_gTxt('your_new_password'), $message);
    $txp_user = $curr_user ;
    return( $out ) ;
}

// -- Resets/changes password and mails it
// -------------------------------------------------------------------
function _cbe_mb_set_author_pass( $new_pass, $name, $email, $return_url, $givenid=null )
{
    $hash = doSlash(txp_hash_password($new_pass));
    $givenid = ($givenid == null ? $name : $givenid) ;

    if( safe_update( 'txp_users', "pass = '$hash'", "name = '".doSlash($name)."'" ) )
        $out = _cbe_mb_gTxt( _cbe_mb_send_new_password( $new_pass, $email, $name, $return_url, $givenid )
                           ? 'password_sent_to'
                           : 'could_not_mail' )
             . ' ' . $givenid ;

    else
        $out = _cbe_mb_gTxt('could_not_update_author').' '.htmlspecialchars( $givenid ) ;
    return( $out ) ;
}
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
h1. cbe_members

It's a client-side plugin.
It currently is a companion for @cbe_frontauth@ that adds the reset and change password features.
Fully plug-and-play: just install and activate, and you're done!
Developed and tested with Textpattern 4.4.1

Claire Brione - http://www.clairebrione.com/

*Requires cbe_frontauth 0.9 and above*

h2. Table of contents

* "Features":#features
* "Download, installation, support":#dl-install-supp
* "Tags list":#tags-list
* "Changelog":#changelog

h2(#features). Features

* **Change password**: displays a form (old password, new password twice), controls datas, if correct changes the password and mails it
* **Reset password**: displays a form (user's name or email depending on @cbe_frontauth@'s parameters), controls datas, if correct resets the password and mails it
* **Error messages**: can be edited in the first lines of plugin code

h2(#dl-install-supp). Download, installation, support

Download from "textpattern resources":http://textpattern.org/plugins/1250/cbe_members or the "plugin page":http://www.clairebrione.com/cbe_members.

Copy/paste in the Admin > Plugins tab to install or uninstall, activate or desactivate.

Visit the "forum thread":http://forum.textpattern.com/viewtopic.php?id=37760 for support.

h2(#tags-list). Tags list

(Later)

h2(#changelog). Changelog

* 28 Mar 12 -v a.1.2- Fix: missing include for password generation
* 21 Mar 12 - v a.1 - Alpha: reset and change password
# --- END PLUGIN HELP ---
-->
<?php
}
?>
