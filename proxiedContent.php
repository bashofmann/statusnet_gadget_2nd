<?php

?>
<script xmlns:os="http://ns.opensocial.org/2008/markup" type="text/os-data">
    <os:PeopleRequest key="Viewer" userId="@viewer" fields="name" groupId="@self"/>
</script>
<script type="text/os-template" xmlns:os="http://ns.opensocial.org/2008/markup" require="Viewer">
    Hello ${Viewer.name.givenName} <b>${Viewer.name.familyName}</b>
</script>