const {
	data: { useSelect, useDispatch },
	plugins: { registerPlugin },
	element: { useState, useEffect },
	components: { CheckboxControl },
	editPost: { PluginDocumentSettingPanel },
} = wp;
const RssOnlyPostSettings = () => {
	const [ isChecked, setChecked ] = useState( _rssonlypost );
	const {
		meta,
		meta: { _rssonlypost },
	} = useSelect((select) => ({
		meta: select('core/editor').getEditedPostAttribute('meta') || {},
	}));

	const { editPost } = useDispatch('core/editor');

	const [rssOnlyPostData, SetRssOnlyPostData] = useState(_rssonlypost);

	useEffect(() => {
		setChecked( rssOnlyPostData);
		editPost({
			meta: {
				...meta,
				_rssonlypost: rssOnlyPostData,
			},
		});
	}, [rssOnlyPostData]);

        

	return (
		<PluginDocumentSettingPanel name="rop" title="RSS only post">
            <CheckboxControl
            label="Display post only in feed"
            checked={ isChecked }
            onChange={ SetRssOnlyPostData }
        />
		</PluginDocumentSettingPanel>
	);
};

if (window.pagenow === 'post') {
	registerPlugin('rssonlypost', {
		render: RssOnlyPostSettings,
		icon: null,
	});
}
