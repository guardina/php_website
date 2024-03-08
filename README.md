# php_website

A little repository to test out php, mysql and online visibility.

### <u>Repositories</u>

#### css: 
Contains css file to define the style of some html parts. So far only the color red and the layout of the buttons were defined.

#### data: 
Contains the XML file with all the data downloaded from refdata and the script to save all the data in the database

#### excel:
Holds excel files that define the tables for the FIRE project database

#### html:
Default html folder that contains some html (not used)

#### php:
Contains all of the scripts in php for the core part of the code:
<li><b>controller_db.php:</b> Manages all operations to the database</li>
<li><b>download_ids.php:</b> Check all ids for the doctors and saves into a database if the id is linked to a doctor or not</li>
<li><b>download_medreg.php:</b> Downloads and stores into the database all data for doctors from medreg, psyreg and betreg</li>
<li><b>download_refdata.php:</b> Downloads all data from refdata and stores it into a XML file</li>
<li><b>medreg_HTTP_controller.php:</b> Executes multiple HTTP requests at once</li>
<li><b>name_mapper.php:</b> Maps the keys of the downloaded data to the names of the columns in the database, to achieve consistency</li>
<li><b>save_refdata.php</b> Stores the data saved into the XML file and saves it into the database</li>


#### sql:
Contains sql scripts to create SQL tables