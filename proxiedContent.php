<?php
//require the php OAuth library
require_once "lib/oauth.php";

class MyOAuthSignatureMethod_RSA_SHA1 extends OAuthSignatureMethod_RSA_SHA1 {
	protected function fetch_public_cert(&$request) {
	    $s = curl_init();
		curl_setopt($s,CURLOPT_URL,$_GET['xoauth_signature_publickey']);
		curl_setopt($s, CURLOPT_RETURNTRANSFER, 1);
		$cert = curl_exec($s);
		curl_close($s);
		return $cert;
	}
	protected function fetch_private_cert(&$request) {
		return;
	}
}

$request = OAuthRequest::from_request();
$server = new MyOAuthSignatureMethod_RSA_SHA1();


$return = $server->check_signature($request, null, null, $_GET['oauth_signature']);

if (! $return) {
	die('invalid signature');
}

$data = json_decode(file_get_contents('php://input'), true);
?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.4/jquery.min.js" />

<script xmlns:os="http://ns.opensocial.org/2008/markup" type="text/os-data">
    <os:PeopleRequest key="Viewer" userId="@viewer" fields="name" groupId="@self"/>
</script>
<script type="text/os-template" xmlns:os="http://ns.opensocial.org/2008/markup" require="Viewer">
    Hello ${Viewer.name.givenName} <b>${Viewer.name.familyName}</b>
</script>

<h3>Your friends:</h3>

<?php if(isset($data[0]) && isset($data[0]['result']) && isset($data[0]['result']['list'])): ?>
<ul>
    <?php foreach($data[0]['result']['list'] as $friend): ?>
    <li>
        <?php echo $friend['displayName'] ?>
    </li>
    <?php endforeach; ?>
</ul>
<?php endif; ?>


<script type="text/os-template" xmlns:os="http://ns.opensocial.org/2008/markup" xmlns:statusnet="http://dev.status.net"
        require="feed" autoUpdate="true">
    <h3>Your status.net feed:</h3>
    <ul>
        <li repeat="${feed}">
            <statusnet:feedItem showMessageLink="true" item="${Cur}"/>
        </li>
    </ul>
</script>

<script type="text/javascript">
    function loadFeed() {
        var fetchData = function() {
            var params = {};
            params[gadgets.io.RequestParameters.AUTHORIZATION] = gadgets.io.AuthorizationType.OAUTH;
            params[gadgets.io.RequestParameters.CONTENT_TYPE] = gadgets.io.ContentType.JSON;
            params[gadgets.io.RequestParameters.OAUTH_SERVICE_NAME]='statusnet';
            gadgets.io.makeRequest('http://dev.status.net:8080/index.php/api/statuses/home_timeline.json', function(response) {

                if (response.oauthApprovalUrl) {
                    var popup = new gadgets.oauth.Popup(
                        response.oauthApprovalUrl,
                        'width=400&height=400',
                        function() { },
                        function() {
                            fetchData();
                        }
                        );

                    popup.onClick_();
                } else {
                    console.log(response);
                    opensocial.data.DataContext.putDataSet('feed', response.data);
                    gadgets.window.adjustHeight();
                    bindLinks();
                }
            }, params);
        }

        fetchData();
    }
    function bindLinks() {
        $('a.link_send_message').unbind('click').click(function() {
            var params = [];
            params[opensocial.Message.Field.TYPE] = opensocial.Message.Type.PRIVATE_MESSAGE;
            params[opensocial.Message.Field.TITLE] = 'A message from the status.net Gadget';
            var message = opensocial.newMessage('What do you think about this update: ' + $('#status_text_' + $(this).attr('id').replace('m_', '')).text(), params);
            opensocial.requestSendMessage(null, message);
        });
        $('a.link_send_activity').unbind('click').click(function() {
            var params = [];
            params[opensocial.Activity.Field.TITLE] = 'A message from the status.net Gadget';
            params[opensocial.Activity.Field.BODY] = 'What do you think about this update: ' + $('#status_text_' + $(this).attr('id').replace('a_', '')).text();
            params[opensocial.Activity.Field.EMBEDS] = [
                {
                    gadget: "http://localhost:8080/statusnet_gadget_2nd/statusnet_gadget.xml",
                    context: $(this).attr('id').replace('a_', '')
                }
            ];
            var activity = opensocial.newActivity(params);
            osapi.activities.create({
                userId: '@viewer',
                activity: activity.toJsonObject()
            }).execute(function() {
                alert('Activity sent');
            });
        });
    }
    gadgets.util.registerOnLoadHandler(function() {
        loadFeed();
    });
</script>
