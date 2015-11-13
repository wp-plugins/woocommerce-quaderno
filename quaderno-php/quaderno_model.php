<?php
/**
* Quaderno Load
*
* Interface for every model
*
* @package   Quaderno PHP
* @author    Quaderno <hello@quaderno.io>
* @copyright Copyright (c) 2015, Quaderno
* @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
*/

if ( ! defined( 'ABSPATH' ) ) { 
    exit; // Exit if accessed directly
}

abstract class QuadernoModel extends QuadernoClass {
	/**
	*  Find for QuadernoModel objects
	* If $params is a single value, it returns a single object
	* If $params is null or an array, it returns an array of objects
	* When request fails, it returns false 
	*/
	public static function find($params = array('page' => 1))
	{
		$return = false;
		$class = get_called_class();

		if (!is_array($params))
		{
			// Searching for an ID
			$response = QuadernoBase::findByID(static::$model, $params);
			if (QuadernoBase::responseIsValid($response)) $return = new $class($response['body']);
		}
		else
		{
			$response = QuadernoBase::find(static::$model, $params);

			if (QuadernoBase::responseIsValid($response))
			{
				$return = array();
				$length = count($response['body']);
				for ($i = 0; $i < $length; $i++)
					$return[$i] = new $class($response['body'][$i]);
			}
		}

		return $return;
	}

	/**
	* Save for QuadernoModel objects
	* Export object data to the model
	* Returns true or false whether the request is accepted or not
	*/
	public function save()
	{
		$response = null;
		$new_object = false;
		$return = false;

		/**
		* 1st step - New object to be created 
		* Check if the current object has not been created yet
		*/
		if (is_null($this->id))
		{
			// Not yet created, let's do it
			$response = QuadernoBase::save(static::$model, $this->data, $this->id);
			$new_object = true;

			/* Update data with the response */
			if (QuadernoBase::responseIsValid($response))
			{
				$this->data = $response['body'];
				$return = true;
			}
			elseif (isset($response['body']['errors']))
				$this->errors = $response['body']['errors'];
		}

		$response = null;
		$new_data = false;

		/**
		* 2nd step - Payments to be created
		* Check if there are any payments stored and not yet created
		*/
		if (isset($this->payments_array) && count($this->payments_array))
		{
			foreach ($this->payments_array as $index => $p)
				if (is_null($p->id))
				{
					// The payment does not have ID -> Not yet created
					$response = QuadernoBase::saveNested(static::$model, $this->id, 'payments', $p->data);
					if (QuadernoBase::responseIsValid($response))
					{
						$p->data = $response['body'];
						$new_data = self::find($this->id);
					}
					elseif (isset($response['body']['errors']))
						$this->errors = $response['body']['errors'];
				}
				if ($p->mark_to_delete)
				{
					// The payment is marked to delete -> Let's do it.
					$delete_response = QuadernoBase::deleteNested(static::$model, $this->id, 'payments', $p->id);
					if (QuadernoBase::responseIsValid($delete_response))
						array_splice($this->payments_array, $index, 1);
					elseif (isset($response['body']['errors']))
						$this->errors = $response['body']['errors'];
				}

			/* If this object has received new data, let's update data field. */
			if ($new_data) $this->data = $new_data->data;
		}

		/**
		* 3rd step - Update object
		* Update object - This is only necessary when it's not a new object, or new payments have been created.
		*/
		if (!$new_object || $new_data)
		{
			$response = QuadernoBase::save(static::$model, $this->data, $this->id);

			if (QuadernoBase::responseIsValid($response))
			{
				$return = true;
				$this->data = $response['body'];
			}
			elseif (isset($response['body']['errors']))
				$this->errors = $response['body']['errors'];
		}

		return $return;
	}

	/**
	* Delete for QuadernoModel objects
	* Delete object from the model
	* Returns true or false whether the request is accepted or not
	*/
	public function delete()
	{
		$return = false;
		$response = QuadernoBase::delete(static::$model, $this->id);

		if (QuadernoBase::responseIsValid($response))
		{
			$return = true;
			$this->data = array();
		}
		elseif (isset($response['body']['errors']))
			$this->errors = $response['body']['errors'];

		return $return;
	}

}
?>