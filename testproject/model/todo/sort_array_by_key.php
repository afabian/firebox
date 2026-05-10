<?php

function sort_array_by_key($mydata, $keyname)
{
	for ($i=0; $i<count($mydata); $i++)
	{
		$swapped = false;
		for ($j=$i+1; $j<count($mydata); $j++)
		{
			if ($mydata[$i][$keyname] > $mydata[$j][$keyname])
			{
				$swapped = true;
				$temp = $mydata[$i];
				$mydata[$i] = $mydata[$j];
				$mydata[$j] = $temp;
			}
		}
		if (!$swapped) break;
	}
	return($mydata);
}

function test_sort_array_by_key()
{
	$passing = true;

	$input    = array(array('a' => 2, 'b' => 1), array('a' => 3, 'b' => 2), array('a' => 1, 'b' => 3));
	$expected = array(array('a' => 1, 'b' => 3), array('a' => 2, 'b' => 1), array('a' => 3, 'b' => 2));
	$output   = sort_array_by_key($input, 'a');

	if ($output != $expected)
	{
		debug("Array didn't sort properly.", __FILE__, __LINE__);
		debug("  Input:    " . var_export($input, true), __FILE__, __LINE__);
		debug("  Expected: " . var_export($expected, true), __FILE__, __LINE__);
		debug("  Output:   " . var_export($output, true), __FILE__, __LINE__);
		$passing = false;
	}

	return($passing);
}
