<h1><?php echo $NAME?></h1>
<div class="curr-version">
    <p><strong><?php p_("Installed Version")?>:</strong> <?php echo $VERSION?></p>
</div>
<div class="copyright">
    <p>
        <?php echo $DESCRIPTION?>
        <br>
        <?php echo $COPYRIGHT?>
    </p>
    <p>
        This program is free software; you can redistribute it and/or modify
        it under the terms of the GNU General Public License as published by
        the Free Software Foundation; either version 2 of the License, or
        (at your option) any later version.
    </p>
    <p>
        This program is distributed in the hope that it will be useful,
        but WITHOUT ANY WARRANTY; without even the implied warranty of
        MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
        GNU General Public License for more details.
    </p>
    <p>
        You should have received a copy of the GNU General Public License
        along with this program; if not, write to the Free Software
        Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
    </p>
</div>
<div class="acknowledgements">
    <h3><?php p_('Ackowledgements')?></h3>
    <p><?php pf_(
        'XML-RPC for PHP (%s) by Edd Dumbill &copy; 1999-2002', '
				  <a href="http://sourceforge.net/projects/phpxmlrpc">http://sourceforge.net/projects/phpxmlrpc</a>'
    )?>
    </p>
</div>
