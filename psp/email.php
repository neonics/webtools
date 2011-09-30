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
		global $db, $request; // XXX ref

		if ( $this->isAction( "send" ) )
		{
			foreach ( $_REQUEST as $k=>$v )
			{
				debug( "REQUEST: '$k' => '$v'");
			}


			$senderName = addslashes( psp_arg( "email:sender:name" ) );
			$senderEMail = psp_arg( "email:sender:email" );
			$emailSubject = addslashes( psp_arg( "email:subject" ) );
			$emailBody = psp_arg( "email:body" );
debug("sender: $senderEMail");
			$ve=null;
			if ( !self::validate( $senderEMail, $ve ) )
			{
				$this->errorMessage( "Illegal sender email: $ve" );
			}
			else
			{
				if ( mail( "doesniedoen@gmail.com",
						$emailSubject,
						preg_replace("#(?<!\r)\n#si", "\r\n", $emailBody ),
						"From: \"$senderName\" <$senderEMail>\r\n"
						#."Return-Path: <>\r\n"
					) )
				{
					$this->message( "Message sent" );
				}
				else
				{
					$this->errorMessage( "Sending message failed." );
				}
			}
		}
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
}

$email_class = "EMailModule";
?>
