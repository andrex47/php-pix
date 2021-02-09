<?php
namespace Piggly\Pix;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Exception;
use Piggly\Pix\Parser;

/**
 * The Pix Payload class.
 * 
 * This is used to set up pix data and follow the EMV®1 pattern and standards.
 * When set up all data, the export() method will generate the full pix payload.
 *
 * @since      1.0.0 
 * @package    Piggly\Pix
 * @subpackage Piggly\Pix
 * @author     Caique <caique@piggly.com.br>
 */
class Payload
{
	/** @var string Version of QRCPS-MPM payload. */
	const ID_PAYLOAD_FORMAT_INDICATOR = '00';
	/** @var string Point of initiation method. When set to 12, means can be used only one time. */
	const ID_POINT_OF_INITIATION_METHOD = '01';
	/** @var string Merchant account information. */
	const ID_MERCHANT_ACCOUNT_INFORMATION = '26';
	/** @var string Merchant account GUI information */
	const ID_MERCHANT_ACCOUNT_INFORMATION_GUI = '00';
	/** @var string Merchant account key information */
	const ID_MERCHANT_ACCOUNT_INFORMATION_KEY = '01';
	/** @var string Merchant account description information */
	const ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION = '02';
	/** @var string Merchant account url information */
	const ID_MERCHANT_ACCOUNT_INFORMATION_URL = '25';
	/** @var string Merchant account category code */
	const ID_MERCHANT_CATEGORY_CODE = '52';
	/** @var string Transaction currency code */
	const ID_TRANSACTION_CURRENCY = '53';
	/** @var string Transaction amount code */
	const ID_TRANSACTION_AMOUNT = '54';
	/** @var string Country code */
	const ID_COUNTRY_CODE = '58';
	/** @var string Merchant name */
	const ID_MERCHANT_NAME = '59';
	/** @var string Merchant city */
	const ID_MERCHANT_CITY = '60';
	/** @var string Additional data field */
	const ID_ADDITIONAL_DATA_FIELD_TEMPLATE = '62';
	/** @var string Additional data field TID */
	const ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TID = '05';
	/** @var string CRC16 */
	const ID_CRC16 = '63';

	/** @var string OUTPUT_SVG Return QR Code in SVG. */
	const OUTPUT_SVG = QRCode::OUTPUT_MARKUP_SVG;
	/** @var string OUTPUT_PNG Return QR Code in PNG. */
	const OUTPUT_PNG = QRCode::OUTPUT_IMAGE_PNG;
	
	/**
	 * Pix key.
	 * @since 1.0.0
	 * @var string
	 */
	protected $pixKey;

	/**
	 * Payment description.
	 * @since 1.0.0
	 * @var string
	 */
	protected $description;

	/**
	 * Merchant name.
	 * @since 1.0.0
	 * @var string
	 */
	protected $merchantName;

	/**
	 * Merchant city.
	 * @since 1.0.0
	 * @var string
	 */
	protected $merchantCity;

	/**
	 * Pix Transaction ID.
	 * @since 1.0.0
	 * @var string
	 */
	protected $tid;

	/**
	 * Transaction amount.
	 * @since 1.0.0
	 * @var string
	 */
	protected $amount;

	/**
	 * Defines if payment is reusable.
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $reusable = false;

	/**
	 * The current pix code mounted.
	 * @since 1.0.0
	 * @var boolean
	 */
	protected $pixCode = null;

	/**
	 * Set the current pix key.
	 * 
	 * EMV -> ID 26 . ID 01
	 * 
	 * @param string $keyType Pix key type.
	 * @param string $pixKey Pix key.
	 * @since 1.0.0
	 * @return self
	 * @throws Exception
	 */
	public function setPixKey ( string $keyType, string $pixKey )
	{
		// Validate Key
		Parser::validate($keyType, $pixKey);
		// Parse Key
		$this->pixKey = Parser::parse($keyType, $pixKey); 
		return $this; 
	}

	/**
	 * Set the current pix description.
	 * 
	 * EMV -> ID 26 . ID 02
	 * Max length 36
	 * 
	 * @param string $description Pix description.
	 * @since 1.0.0
	 * @return self
	 */
	public function setDescription ( string $description )
	{ $this->description = $this->applyLength($description, 36); return $this; }

	/**
	 * Set the current pix merchant name.
	 * 
	 * EMV -> ID 59
	 * Max length 25
	 * 
	 * @param string $merchantName Pix merchant name.
	 * @since 1.0.0
	 * @since 1.0.2 Removed character limit.
	 * @return self
	 */
	public function setMerchantName ( string $merchantName )
	{ $this->merchantName = $this->applyLength($merchantName); return $this; }

	/**
	 * Set the current pix merchant city.
	 * 
	 * EMV -> ID 60
	 * Max length 15
	 * 
	 * @param string $merchantCity Pix merchant city.
	 * @since 1.0.0
	 * @since 1.0.2 Removed character limit.
	 * @return self
	 */
	public function setMerchantCity ( string $merchantCity )
	{ $this->merchantCity = $this->applyLength($merchantCity); return $this; }

	/**
	 * Set the current pix transaction id.
	 * 
	 * EMV -> ID 62 . ID 05
	 * Max length 25
	 * 
	 * @param string $tid Pix transaction id.
	 * @since 1.0.0
	 * @return self
	 */
	public function setTid ( string $tid )
	{ $this->tid = $this->applyLength($tid, 25); return $this; }

	/**
	 * Set the current pix transaction amount.
	 * 
	 * EMV -> ID 54
	 * Max length 13 0000000.00
	 * 
	 * @param string $amount Pix transaction amount.
	 * @since 1.0.0
	 * @return self
	 * @throws Exception When amount is greater than max length.
	 */
	public function setAmount ( float $amount )
	{ $this->amount = $this->applyLength((string) number_format( $amount, 2, '.', '' ), 13, true); return $this; }

	/**
	 * Set the if the current pix can or can not be reusable.
	 * 
	 * EMV -> ID 01
	 * 
	 * @param string $reusable If pix can be reusable.
	 * @since 1.0.0
	 * @return self
	 */
	public function setAsReusable ( bool $reusable = true )
	{ $this->reusable = $reusable; return $this; }

	/**
	 * Get the current pix code.
	 * 
	 * @since 1.0.0
	 * @return string
	 * @throws Exception When something went wrong.
	 */
	public function getPixCode () : string
	{
		$this->pixCode = 
			$this->getPayloadFormat() .
			$this->getPointOfInitiationMethod() .
			$this->getMerchantAccountInformation() .
			$this->getMerchantCategoryCode() .
			$this->getTransactionCurrency() .
			$this->getTransactionAmount() .
			$this->getCountryCode() .
			$this->getMerchantName() .
			$this->getMerchantCity() .
			$this->getAdditionalDataFieldTemplate();

		$this->pixCode .= $this->getCRC16($this->pixCode);
		return $this->pixCode;	
	}

	/**
	 * Return the qr code based in current pix code.
	 * The qr code format is a base64 image/png.
	 * 
	 * @param string $imageType Type of output image.
	 * @since 1.0.0
	 * @since 1.0.2 Added support for output image.
	 * @return string
	 * @throws Exception When something went wrong.
	 */
	public function getQRCode ( string $imageType = self::OUTPUT_SVG ) : string
	{ 
		$options = new QROptions([
			'outputLevel' => QRCode::ECC_M,
			'outputType' => $imageType
		]);

		if ( empty( $this->pixCode ) ) 
		{ $this->getPixCode(); }

		return (new QRCode($options))->render($this->pixCode); 
	}

	/**
	 * Get the current payload format. Default is 01.
	 * 
	 * EMV -> ID 00
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getPayloadFormat ()
	{ return '000201'; }

	/**
	 * Get the current point of initiation method.
	 * 
	 * EMV -> ID 01
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getPointOfInitiationMethod ()
	{
		return 
			$this->reusable ?
				// Unique
				$this->formatID(
					self::ID_POINT_OF_INITIATION_METHOD,
					'12'
				) :
				// Reusable
				$this->formatID(
					self::ID_POINT_OF_INITIATION_METHOD,
					'11'
				);
	}

	/**
	 * Get the current merchant account information.
	 * 
	 * EMV -> ID 26
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getMerchantAccountInformation () : string
	{
		// Global bank domain
		// ID 00
		$gui = $this->formatID(
			self::ID_MERCHANT_ACCOUNT_INFORMATION_GUI,
			'br.gov.bcb.pix'
		);

		// Current pix key
		// ID 01
		$key = $this->formatID(
			self::ID_MERCHANT_ACCOUNT_INFORMATION_KEY,
			$this->pixKey
		);

		// Current pix description
		// ID 02
		$description = $this->formatID(
			self::ID_MERCHANT_ACCOUNT_INFORMATION_DESCRIPTION,
			$this->description,
			false
		);

		return $this->formatID(
			self::ID_MERCHANT_ACCOUNT_INFORMATION,
			$gui.$key.$description
		);
	}

	/**
	 * Get the current merchant category code.
	 * 
	 * EMV -> ID 52
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getMerchantCategoryCode ()
	{ return '52040000'; }

	/**
	 * Get the current transaction currency.
	 * 
	 * EMV -> ID 53
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getTransactionCurrency () : string 
	{
		return $this->formatID(
			self::ID_TRANSACTION_CURRENCY,
			'986'
		);
	}

	/**
	 * Get the current transaction currency.
	 * 
	 * EMV -> ID 54
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getTransactionAmount () : string 
	{
		return $this->formatID(
			self::ID_TRANSACTION_AMOUNT,
			$this->amount,
			false
		);
	}

	/**
	 * Get the current country code.
	 * 
	 * EMV -> ID 58
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getCountryCode () : string 
	{ return '5802BR'; }

	/**
	 * Get the current merchant name.
	 * 
	 * EMV -> ID 59
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getMerchantName () : string 
	{
		return $this->formatID(
			self::ID_MERCHANT_NAME,
			$this->merchantName
		);
	}

	/**
	 * Get the current merchant city.
	 * 
	 * EMV -> ID 60
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getMerchantCity () : string 
	{
		return $this->formatID(
			self::ID_MERCHANT_CITY,
			$this->merchantCity
		);
	}

	/**
	 * Get the current addictional data field template.
	 * 
	 * EMV -> ID 62
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	protected function getAdditionalDataFieldTemplate ()
	{
		// Current pix transaction id
		$tid = $this->formatID(
			self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE_TID,
			$this->tid,
			false
		);

		return $this->formatID(
			self::ID_ADDITIONAL_DATA_FIELD_TEMPLATE,
			$tid,
			false
		);
	}

	/**
	 * Get the current CRC16 by following standard values provieded by BACEN.
	 * 
	 * @since 1.0.0
	 * @param string $payload The full payload.
	 * @return string
	 */
	protected function getCRC16 ( string $payload )
	{
		// Standard data
		$payload .= self::ID_CRC16.'04';

		// Standard values by BACEN
		$polynomial = 0x1021;
		$response   = 0xFFFF;

		// Checksum
		if ( ( $length = strlen($payload ) ) > 0 ) 
		{
			for ( $offset = 0; $offset < $length; $offset++ ) 
			{
				$response ^= ( ord( $payload[$offset] ) << 8 );
				
				for ( $bitwise = 0; $bitwise < 8; $bitwise++ ) 
				{
					if ( ( $response <<= 1 ) & 0x10000 ) 
					{ $response ^= $polynomial; }

					$response &= 0xFFFF;
				}
			}
	  }

	  // CRC16 calculated
	  return self::ID_CRC16.'04' . strtoupper( dechex( $response ) );
	}

	/**
	 * Return formated data following the EMV patterns.
	 * 
	 * @since 1.0.0
	 * @param string $id Data ID.
	 * @param string|null $value Data value.
	 * @param bool $required When data value is required.
	 * @return string Formated data.
	 * @throws Exception When value is empty and required.
	 */
	protected function formatID ( string $id, $value, bool $required = true ) : string 
	{
		if ( empty( $value ) )
		{ 
			if ( $required ) 
			{ throw new Exception(sprintf('O id `%s` não pode ser vazio.', $id)); }
			else 
			{ return ''; } 
		}

		$valueSize = str_pad( mb_strlen($value), 2, '0', STR_PAD_LEFT );
		return $id.$valueSize.$value;
	}

	/**
	 * Cut data more than $maxLength.
	 * 
	 * @since 1.0.0
	 * @param string $value
	 * @param int $maxLength
	 * @param bool $throws To throw exception when exceed.
	 * @return string
	 * @throws Exception When value exceed max length.
	 */
	private function applyLength ( string $value, int $maxLength = 25, bool $throws = false )
	{
		if ( strlen($value) > $maxLength )
		{ 
			if ( $throws )
			{ throw new Exception(sprintf('O valor `%s` excede o limite do campo.')); }

			return substr($value, 0, 25);
		}

		return $value;
	}
}