/** Íconos que llegan por nombre desde el servidor (módulos, perfiles, productos). */
import {
    LayoutDashboard, Users, Network, Wallet, HandHeart, Store, GraduationCap, MapPin, ScrollText, ShieldCheck,
    Crown, Landmark, Briefcase, UserRound, UserCog, Package, ShoppingBasket, Backpack, Glasses, Shirt, Smile, Shield,
    Pill, Dumbbell, Ticket, Wrench, Smartphone, Circle, HeartHandshake, Megaphone, Star,
} from 'lucide-vue-next';

export const ICONOS: Record<string, any> = {
    'layout-dashboard': LayoutDashboard, users: Users, network: Network, wallet: Wallet, 'hand-heart': HandHeart, store: Store,
    'graduation-cap': GraduationCap, 'map-pin': MapPin, 'scroll-text': ScrollText, 'shield-check': ShieldCheck,
    crown: Crown, landmark: Landmark, briefcase: Briefcase, 'user-round': UserRound, 'user-cog': UserCog,
    package: Package, 'shopping-basket': ShoppingBasket, backpack: Backpack, glasses: Glasses, shirt: Shirt, smile: Smile,
    shield: Shield, pill: Pill, dumbbell: Dumbbell, ticket: Ticket, wrench: Wrench, smartphone: Smartphone,
    'heart-handshake': HeartHandshake, megaphone: Megaphone, star: Star,
};

export const icono = (n?: string | null) => (n && ICONOS[n]) || Circle;

/** Íconos que se pueden elegir para un perfil. */
export const ICONOS_PERFIL = ['user-cog', 'crown', 'landmark', 'briefcase', 'network', 'user-round', 'heart-handshake', 'megaphone', 'star', 'shield'];
