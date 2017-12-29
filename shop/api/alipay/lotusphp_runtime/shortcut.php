<?php
function Ci($className)
{
	return LtObjectUtil::singleton($className);
}
