ATPHP
=======

lightweight PHP prototyping framework aim to rapid develop CRUD backend with MySQL for Mobile App and HTML5 ajax based applications.


With this tiny framwork, it's easy to prototype a simple backend with less than 5 minutes.


Features:

1. Super lightweight(less than 20kb)

2. Login feature

3. List(with optional filters & paging)

4. Get/Select - for getting single object

5. Update

6. Delete

7. Batch Update

8. Batch Add

9. Access(Edit, View)

10. Validating function

11. Memcache/PHP Session support



Setting Up

1. Change the settings in svc.php

2. define MySQL Table Models in include/models.php

3. change the Login, ChkLogin, isLogin function based on your model


Usage:


1. Sample List:

ajax POST to URL: svc.php?m=ToDo&a=List

Note:

m - the Table name 

a - the action



2. Sample Update:

ajax POST to URL: svc.php?m=ToDo&a=Update

JSON illustration of the POST Variable:

{ID : 1, Name : "Buy Milk", Date : "2012-10-11", Completed : "0"}

Note: the key should match the table fields



3. UpdateBatch, and AddBatch are essentially the same, it will automatically check if the records have ID for update or else an Insert will be performed

svc.php?m=ToDo&a=UpdateBatch

JSON illustration of the POST Variable:

{Data : [todo_object_1, todo_object_2,todo_object_3]}

Note: todo_object should match the table fields, see the sample 2.




Customization:

Create your own customize function to replace the default is simple, simply using the TABLENAME+ACTION format, example:

function ToDoList

where m=ToDo, the table name, a=List, the action


sample:

function ToDoList(){

	/*

	do your customize action here

	*/

	Listing('ToDo');

}


example 2: if I want to add a check to issue an error if the Name is too short.

function ToDoUpdate(){

	global $_POST,$Output;

	if(strlen($_POST['Name']) < 10) $Output['Error'] = 'ERR_Name_Too_Short';

	else Update('ToDo');

}



Please refer to the code for better understanding of the features and usages, feel free to ask questions any time.