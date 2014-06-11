<?php
/**
 * author: Kenney Westerhof <kenney@neonics.com>
 */

class EMailModule extends AbstractModule
{
	private $templateId;

	public function __construct()
	{
		parent::__construct( 'email', "http://neonics.com/2011/psp/email" );
		debug("!!!!!!!!!!!!!!!!!!!!!!");
	}

	public function setParameters( $xslt )
	{
		# NOTE: this MUST be done as the XSL requires the variable
		# as it uses it in <xsl:param name="a:template" select="$template"/>
		# as that seems the only thing that works with namespaced vars..
	#	$xslt->setParameter( $this->ns, "template", $this->templateId );
	}


	/***** Public Interface *****/
	

	/**
	 *
	 */
	function init()
	{
		global $request, $debug; // XXX ref

		$this->status = 'form';

		if ( $this->isAction( "send" ) )
		{

			foreach ( $_REQUEST as $k=>$v )
			{
				debug( "REQUEST: '$k' => '$v'");
			}

			$recipientEmail;
			{
				$fid = psp_arg( "email:form:id" );
				$v = gad( $_SESSION, "email:form:id", Array() );
				$recipientEmail = gad( $v, 'id:'.$fid, null );
				unset( $v['id:'.$fid] );
				unset( $v['email:'.$recipientEmail] );
				$_SESSION["mail:form:id"] = $v;
			}


			if ( !isset( $recipientEmail ) )
			{
				$this->errorMessage( "E-Mail recipient is not configured" );
				echo( "E-Mail recipient is not configured" );
				$this->status = 'fail';
				return;
			}

			$senderName = addslashes( psp_arg( "email:sender:name" ) );
			$senderEMail = psp_arg( "email:sender:email" );
			$emailSubject = "FractalFountain webform: " . addslashes( psp_arg( "email:subject" ) );
			$emailBody = psp_arg( "email:body" );
			$emailBody = preg_replace("#(?<!\r)\n#si", "\r\n", $emailBody );
			$emailHeaders = "From: $senderName <$senderEMail>";

			$emailBody = "PAGE: " . requestURL()
				. "\nRemote Address: ".$_SERVER["REMOTE_ADDR"]
				. "\n\n$emailBody";
			if ( true )
			{
				$emailBody = str_replace("\r\n", "\n", $emailHeaders)."\n\n$emailBody";
				$emailHeaders = null;
			}
				
			if ( $debug > 2 )
			{
				debug( "To: $recipientEmail" );
				debug( "Subject: $emailSubject" );
				debug( "Headers:" );
				debug( str_replace("<", "&lt;", $emailHeaders) );
				debug( "Body:\n$emailBody" );
			}

			$ve=null;
			if ( !self::validate( $senderEMail, $ve ) )
			{
				$this->errorMessage( "Illegal sender email: $ve" );
			}
			else
			{
				if ( mail( $recipientEmail,//"doesniedoen@gmail.com",
						$emailSubject,
						$emailBody,
						$emailHeaders
					) )
				{
					$this->message( "Message sent" );
					$this->status = 'sent';
				}
				else
				{
					$err = error_get_last();
					echo "<pre>ERROR: $err; ";var_dump($err);echo "</pre>";
					$this->errorMessage( "Sending message failed." );
					$this->errorMessage( $err );
					$this->status = 'fail';
				}
			}
		}
	}

	public function status()
	{
		return $this->status;
	}

	private static function validate( $email, &$errormsg = null )
	{
/**
http://www.linuxjournal.com/article/9585?page=0,3	

Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email 
address format and the domain exists.
*/
		 $isValid = true;
		 $atIndex = strrpos($email, "@");
		 if (is_bool($atIndex) && !$atIndex)
		 {
				$isValid = false;
				$errormsg = "No @, You're not even trying!";
		 }
		 else
		 {
				$domain = substr($email, $atIndex+1);
				$local = substr($email, 0, $atIndex);
				$localLen = strlen($local);
				$domainLen = strlen($domain);
				if ($localLen < 1 || $localLen > 64)
				{
					 // local part length exceeded
					 $isValid = false;
				}
				else if ($domainLen < 1 || $domainLen > 255)
				{
					 // domain part length exceeded
					 $isValid = false;
				}
				else if ($local[0] == '.' || $local[$localLen-1] == '.')
				{
					 // local part starts or ends with '.'
					 $isValid = false;
				}
				else if (preg_match('/\\.\\./', $local))
				{
					 // local part has two consecutive dots
					 $isValid = false;
				}
				else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
				{
					 // character not valid in domain part
					 $isValid = false;
				}
				else if (preg_match('/\\.\\./', $domain))
				{
					 // domain part has two consecutive dots
					 $isValid = false;
				}
				else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
									 str_replace("\\\\","",$local)))
				{
					 // character not valid in local part unless 
					 // local part is quoted
					 if (!preg_match('/^"(\\\\"|[^"])+"$/',
							 str_replace("\\\\","",$local)))
					 {
							$isValid = false;
					 }
				}
				if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
				{
					 // domain not found in DNS
					$isValid = false;
					$errormsg = "Unknown sender domain";
				}
		 }
		 return $isValid;
	}

	/******* XSL functions **********/

	public function form( $email )
	{
		//echo "FORM: email=$email<br/>";

	#unset( $_SESSION["mail:form:id"] );
	#unset( $_SESSION["email:form:id"] );

		$v = gad( $_SESSION, "email:form:id", Array() );

		$id = gad( $v, 'email:'.$email, rand() );

		$v['id:'.$id] = $email;
		$v['email:'.$email] = $id;

		$_SESSION['email:form:id'] = $v;

		//echo "SESSION HASH: "; var_dump( $v );
		//var_dump($_SESSION);
		return $id;
	}
}

$email_class = "EMailModule";
?>
