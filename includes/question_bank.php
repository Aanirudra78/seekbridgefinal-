<?php
// question_bank.php - Shared MCQ bank keyed by skill name
// Used by skill_assessment.php (student test) and by industry
// test autogeneration.

$question_bank = array(
    'Python' => array(
        array('q' => 'Which keyword is used to define a function in Python?', 'o' => array('def', 'func', 'function', 'define'), 'a' => 0),
        array('q' => 'What data type is the result of `type([])`?', 'o' => array('Tuple', 'List', 'Dictionary', 'Set'), 'a' => 1),
        array('q' => 'Which of these is a Python package for data analysis?', 'o' => array('React', 'Laravel', 'Pandas', 'Flutter'), 'a' => 2),
        array('q' => 'What does PEP 8 define in Python?', 'o' => array('A package manager', 'A style guide', 'A testing tool', 'A web framework'), 'a' => 1),
        array('q' => 'Which is a Python web framework?', 'o' => array('Django', 'Spring', 'Rails', 'Laravel'), 'a' => 0),
    ),
    'Java' => array(
        array('q' => 'Which keyword is used to define a class in Java?', 'o' => array('class', 'define', 'object', 'struct'), 'a' => 0),
        array('q' => 'Which method is the entry point of a Java program?', 'o' => array('start()', 'main()', 'run()', 'init()'), 'a' => 1),
        array('q' => 'Java is a _______ language.', 'o' => array('Compiled', 'Interpreted', 'Both', 'Neither'), 'a' => 2),
        array('q' => 'The JVM stands for _______.', 'o' => array('Java Virtual Machine', 'Java Version Manager', 'Java Variable Module', 'Jade Virtual Machine'), 'a' => 0),
        array('q' => 'Which of these is NOT a Java primitive type?', 'o' => array('int', 'float', 'String', 'boolean'), 'a' => 2),
    ),
    'SQL' => array(
        array('q' => 'Which command is used to retrieve data from a database?', 'o' => array('INSERT', 'UPDATE', 'SELECT', 'DELETE'), 'a' => 2),
        array('q' => 'Which clause is used to filter records?', 'o' => array('WHERE', 'GROUP BY', 'ORDER BY', 'HAVING'), 'a' => 0),
        array('q' => 'Which statement creates a new table?', 'o' => array('CREATE TABLE', 'ALTER TABLE', 'NEW TABLE', 'ADD TABLE'), 'a' => 0),
        array('q' => 'Which join returns only matching rows from both tables?', 'o' => array('INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL JOIN'), 'a' => 0),
        array('q' => 'Which keyword removes duplicate rows from a SELECT result?', 'o' => array('UNIQUE', 'DISTINCT', 'DIFFERENT', 'ONLY'), 'a' => 1),
    ),
    'Machine Learning' => array(
        array('q' => 'Which algorithm is used for classification?', 'o' => array('Linear Regression', 'Logistic Regression', 'K-Means', 'Apriori'), 'a' => 1),
        array('q' => 'What does "supervised learning" require?', 'o' => array('Unlabeled data', 'Labeled data', 'No data', 'Only features'), 'a' => 1),
        array('q' => 'Which library is popular for ML in Python?', 'o' => array('NumPy', 'Scikit-learn', 'Pandas', 'Matplotlib'), 'a' => 1),
        array('q' => 'Overfitting means the model _______.', 'o' => array('Learns training data too well', 'Learns too little', 'Is too slow', 'Has no data'), 'a' => 0),
        array('q' => 'Which is an unsupervised learning algorithm?', 'o' => array('K-Means clustering', 'Linear Regression', 'Decision Tree', 'Logistic Regression'), 'a' => 0),
    ),
    'Data Analysis' => array(
        array('q' => 'Which format is commonly used for tabular data?', 'o' => array('CSV', 'MP4', 'PDF', 'PNG'), 'a' => 0),
        array('q' => 'Which of the following best describes "data cleaning"?', 'o' => array('Deleting data', 'Handling missing/incorrect data', 'Visualizing data', 'Storing data'), 'a' => 1),
        array('q' => 'Which tool is widely used for data visualisation?', 'o' => array('Tableau', 'Photoshop', 'AutoCAD', 'Excel only'), 'a' => 0),
        array('q' => 'What does a bar chart best show?', 'o' => array('Comparison across categories', 'Relationships between variables', 'Distribution trends', 'Network structure'), 'a' => 0),
        array('q' => 'The mean is also known as the _______.', 'o' => array('Average', 'Median', 'Mode', 'Range'), 'a' => 0),
    ),
    'Web Development' => array(
        array('q' => 'Which language defines the structure of a web page?', 'o' => array('CSS', 'HTML', 'JavaScript', 'PHP'), 'a' => 1),
        array('q' => 'Which is a popular CSS framework?', 'o' => array('Bootstrap', 'Node', 'Python', 'MySQL'), 'a' => 0),
        array('q' => 'HTTP is a protocol used for...', 'o' => array('Emails', 'Web communication', 'File compression', 'Cryptography'), 'a' => 1),
        array('q' => 'Which language adds interactivity to a webpage?', 'o' => array('JavaScript', 'SQL', 'HTML', 'CSS'), 'a' => 0),
        array('q' => 'What does REST stand for in web APIs?', 'o' => array('Representational State Transfer', 'Remote State Test', 'Random Server Tool', 'Rapid Environment Setup'), 'a' => 0),
    ),
    'Networking' => array(
        array('q' => 'What layer of the OSI model deals with routing?', 'o' => array('Physical', 'Network', 'Session', 'Application'), 'a' => 1),
        array('q' => 'Which protocol translates domain names to IP addresses?', 'o' => array('HTTP', 'FTP', 'DNS', 'SMTP'), 'a' => 2),
        array('q' => 'An IP address consists of how many octets in IPv4?', 'o' => array('2', '4', '6', '8'), 'a' => 1),
        array('q' => 'A router operates mainly at which OSI layer?', 'o' => array('Layer 1', 'Layer 3', 'Layer 5', 'Layer 7'), 'a' => 1),
        array('q' => 'Which device connects multiple devices on the same network?', 'o' => array('Switch', 'Modem only', 'Router only', 'Server'), 'a' => 0),
    ),
    'Cloud Computing' => array(
        array('q' => 'Which is a major cloud service provider?', 'o' => array('AWS', 'Java', 'Windows 8', 'Photoshop'), 'a' => 0),
        array('q' => 'IaaS, PaaS, and SaaS are types of _______.', 'o' => array('Networks', 'Cloud services', 'Databases', 'Protocols'), 'a' => 1),
        array('q' => 'Storing data in the cloud means storing it on...', 'o' => array('Local PC', 'Remote servers', 'USB drive', 'RAM'), 'a' => 1),
        array('q' => 'Which cloud model gives the most control to the user?', 'o' => array('IaaS', 'PaaS', 'SaaS', 'FaaS'), 'a' => 0),
        array('q' => 'Scaling resources automatically based on demand is called _______.', 'o' => array('Auto-scaling', 'Manual scaling', 'Load balancing only', 'Caching'), 'a' => 0),
    ),
    'Communication' => array(
        array('q' => 'What is the key to effective verbal communication?', 'o' => array('Speaking fast', 'Clear and concise speech', 'Using jargon', 'Avoiding eye contact'), 'a' => 1),
        array('q' => 'Active listening involves...', 'o' => array('Interrupting the speaker', 'Focusing and responding', 'Talking over others', 'Checking phone'), 'a' => 1),
        array('q' => 'Which is an example of non-verbal communication?', 'o' => array('Body language', 'Email', 'Report', 'Memo'), 'a' => 0),
        array('q' => 'Which is a good practice in emails?', 'o' => array('A clear subject line', 'All caps text', 'No greeting', 'Vague wording'), 'a' => 0),
        array('q' => 'Constructive feedback should be _______.', 'o' => array('Specific and actionable', 'Vague', 'Public only', 'Personal'), 'a' => 0),
    ),
    'Leadership' => array(
        array('q' => 'Which quality is important for a leader?', 'o' => array('Empathy', 'Rudeness', 'Isolation', 'Micromanaging'), 'a' => 0),
        array('q' => 'A good leader primarily...', 'o' => array('Dictates orders', 'Inspires and guides the team', 'Does all work alone', 'Avoids decisions'), 'a' => 1),
        array('q' => 'Which activity demonstrates leadership?', 'o' => array('Taking initiative', 'Blaming others', 'Ignoring feedback', 'Avoiding responsibility'), 'a' => 0),
        array('q' => 'Delegation in leadership means _______.', 'o' => array('Assigning tasks to the team', 'Doing everything alone', 'Ignoring the team', 'Micromanaging'), 'a' => 0),
        array('q' => 'A team that trusts its leader is likely to be _______.', 'o' => array('More productive', 'Less motivated', 'Unengaged', 'Unreliable'), 'a' => 0),
    ),
);

/**
 * Return up to $n questions for a given skill (case-insensitive lookup).
 * Returns array of questions, or an empty array if none found.
 */
function get_questions_for_skill($skill_name, $n = 5) {
    global $question_bank;
    foreach ($question_bank as $key => $questions) {
        if (strcasecmp($key, $skill_name) === 0) {
            return array_slice($questions, 0, $n);
        }
    }
    // fuzzy: match if skill_name appears within a bank key, or vice versa
    foreach ($question_bank as $key => $questions) {
        if (stripos($skill_name, $key) !== false || stripos($key, $skill_name) !== false) {
            return array_slice($questions, 0, $n);
        }
    }
    return array();
}
