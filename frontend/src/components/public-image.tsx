import NextImage, { type ImageProps } from 'next/image';
import { publicAsset } from '@/lib/deployment-paths';

export default function PublicImage(props: ImageProps) {
  return <NextImage {...props} src={typeof props.src === 'string' ? publicAsset(props.src) : props.src} />;
}
